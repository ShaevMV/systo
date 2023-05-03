<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Scan;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Baza\Tickets\Applications\Scan\ElTicket\ElTicketQuery;
use Baza\Tickets\Applications\Scan\ElTicket\ElTicketsQueryHandler;
use Baza\Tickets\Applications\Scan\FriendlyTicket\FriendlyTicketQuery;
use Baza\Tickets\Applications\Scan\FriendlyTicket\FriendlyTicketQueryHandler;
use Baza\Tickets\Applications\Scan\LiveTicket\LiveTicketQuery;
use Baza\Tickets\Applications\Scan\LiveTicket\LiveTicketQueryHandler;
use Baza\Tickets\Applications\Scan\SpisokTicket\SpisokTicketQuery;
use Baza\Tickets\Applications\Scan\SpisokTicket\SpisokTicketQueryHandler;
use Baza\Tickets\Services\DefineService;
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
            DefineService::DRUG_TICKET => new FriendlyTicketQuery($searchDto->getId()),
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
