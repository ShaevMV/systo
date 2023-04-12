<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Search;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketQuery;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketsQueryHandler;
use Baza\Tickets\Applications\Search\FriendlyTicket\FriendlyTicketQuery;
use Baza\Tickets\Applications\Search\FriendlyTicket\FriendlyTicketQueryHandler;
use Baza\Tickets\Applications\Search\LiveTicket\LiveTicketQuery;
use Baza\Tickets\Applications\Search\LiveTicket\LiveTicketQueryHandler;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketQuery;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketQueryHandler;
use DomainException;

class SearchEngine
{
    private QueryBus $bus;

    public function __construct(
        private DefineService      $defineService,

        ElTicketsQueryHandler      $elSearchQueryHandler,
        SpisokTicketQueryHandler   $spisokTicketQueryHandler,
        FriendlyTicketQueryHandler $friendlyTicketQueryHandler,
        LiveTicketQueryHandler     $liveTicketQueryHandler,
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            ElTicketQuery::class => $elSearchQueryHandler,
            SpisokTicketQuery::class => $spisokTicketQueryHandler,
            FriendlyTicketQuery::class => $friendlyTicketQueryHandler,
            LiveTicketQuery::class => $liveTicketQueryHandler,
        ]);
    }


    public function get(string $link): TicketResponseInterface
    {
        $searchDto = $this->defineService->getTypeByReference($link);

        $query = match ($searchDto->getType()) {
            DefineService::ELECTRON_TICKET => new ElTicketQuery($searchDto->getId()),
            DefineService::SPISOK_TICKET => new SpisokTicketQuery($searchDto->getId()),
            DefineService::FRIENDLY_TICKET => new FriendlyTicketQuery($searchDto->getId()),
            DefineService::LIVE_TICKET => new LiveTicketQuery($searchDto->getId()),
            default => throw new DomainException('Не верный тип ' . $searchDto->getType()),
        };
        /** @var TicketResponseInterface|null $result */
        $result = $this->bus->ask($query);

        if (is_null($result)) {
            throw new DomainException('Не найден билет: Тип ' . DefineService::HUMAN_LIST[$searchDto->getType()] . ' идентификатор ' . $searchDto->getIdToString());
        }

        return $result;
    }
}
