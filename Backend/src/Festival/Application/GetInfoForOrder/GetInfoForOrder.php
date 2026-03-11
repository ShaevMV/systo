<?php

declare(strict_types=1);

namespace Tickets\Festival\Application\GetInfoForOrder;

use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Festival\Response\InfoForOrderingDto;
use Tickets\Festival\Response\ListTicketTypeDto;
use Tickets\Festival\Response\ListTypesOfPaymentDto;

final class GetInfoForOrder
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        ListTicketTypeQueryHandler $listTicketTypeQueryHandler,
        TypesOfPaymentQueryHandler $typesOfPaymentQueryHandler,
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            ListTicketTypeQuery::class => $listTicketTypeQueryHandler,
            TypesOfPaymentQuery::class => $typesOfPaymentQueryHandler,
        ]);
    }

    public function getInfoForOrderingDto(Uuid $festivalId, bool $isAdmin = false, bool $isPusher = false): InfoForOrderingDto
    {
        /** @var ListTicketTypeDto $listTicketTypeDto */
        $listTicketTypeDto = $this->queryBus->ask(new ListTicketTypeQuery($festivalId, isPusher:$isPusher));

        /** @var ListTypesOfPaymentDto $listTypesOfPaymentDto */
        $listTypesOfPaymentDto = $this->queryBus->ask(new TypesOfPaymentQuery($isAdmin));

        return new InfoForOrderingDto(
            $listTicketTypeDto,
            $listTypesOfPaymentDto
        );
    }

    public function getListTypesOfPaymentDto(Uuid $ticketTypeId): ListTypesOfPaymentDto
    {
        /** @var ListTypesOfPaymentDto $listTypesOfPaymentDto */
        $listTypesOfPaymentDto = $this->queryBus->ask(new TypesOfPaymentQuery(ticketTypeId:$ticketTypeId));

        return $listTypesOfPaymentDto;
    }

    public function getListTicketTypeDto(Uuid $festivalId): ListTicketTypeDto
    {
        /** @var ListTicketTypeDto $listTicketTypeDto */
        $listTicketTypeDto = $this->queryBus->ask(new ListTicketTypeQuery($festivalId));

        return $listTicketTypeDto;
    }

    public function getAllPrice(Uuid $festivalId): ListTicketTypeDto
    {
        /** @var ListTicketTypeDto $listTicketTypeDto */
        $listTicketTypeDto = $this->queryBus->ask(new ListTicketTypeQuery($festivalId, true));
        $result = [];
        foreach ($listTicketTypeDto->getTicketType() as $item) {
            $result[] = $item;
            if(count($item->getPriceList()) > 0) {
                foreach ($item->getPriceList() as $priceDto) {
                    $itemClone = clone $item;
                    $result[] = $itemClone->setPrice($priceDto->getPrice());
                }
            }
        }

        return new ListTicketTypeDto($result);
    }
}
