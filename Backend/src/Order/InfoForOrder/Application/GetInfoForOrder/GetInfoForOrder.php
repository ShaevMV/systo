<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\GetInfoForOrder;

use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceListQuery;
use Tickets\Order\InfoForOrder\Response\InfoForOrderingDto;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Order\InfoForOrder\Response\ListTicketTypeDto;
use Tickets\Order\InfoForOrder\Response\ListTypesOfPaymentDto;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;

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

    public function getInfoForOrderingDto(Uuid $festivalId): InfoForOrderingDto
    {
        /** @var ListTicketTypeDto $listTicketTypeDto */
        $listTicketTypeDto = $this->queryBus->ask(new ListTicketTypeQuery($festivalId));

        /** @var ListTypesOfPaymentDto $listTypesOfPaymentDto */
        $listTypesOfPaymentDto = $this->queryBus->ask(new TypesOfPaymentQuery());

        return new InfoForOrderingDto(
            $listTicketTypeDto,
            $listTypesOfPaymentDto
        );
    }

    public function getAllPrice(Uuid $festivalId): ListTicketTypeDto
    {
        /** @var ListTicketTypeDto $listTicketTypeDto */
        $listTicketTypeDto = $this->queryBus->ask(new ListTicketTypeQuery($festivalId));
        $result=[];
        foreach ($listTicketTypeDto->getTicketType() as $item) {
            $data = $item->toArray();
            if (count($data['ticket_type_price']) > 0) {
                foreach ($data['ticket_type_price'] as $value) {
                    $data['price'] = $value['price'];
                    $result[] = TicketTypeDto::fromState($data);
                }
            }
        }
        return new ListTicketTypeDto($result);
    }
}
