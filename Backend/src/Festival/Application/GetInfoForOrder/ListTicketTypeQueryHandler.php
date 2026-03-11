<?php

declare(strict_types=1);

namespace Tickets\Festival\Application\GetInfoForOrder;


use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\Repositories\TicketTypeInterfaceRepository;
use Tickets\Festival\Response\ListTicketTypeDto;
use Tickets\Festival\Response\TicketTypeDto;

final class ListTicketTypeQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeInterfaceRepository $ticketType,
    )
    {
    }

    public function __invoke(ListTicketTypeQuery $query): ListTicketTypeDto
    {
        $result = $this->ticketType->getList(
            $query->getFestivalId(),
            $query->isAllPrice(),
        );


        if ($query->isPusher()) {
            $newResult = [];
            foreach ($result as $item) {
                if (!$item->getId()->equals(new Uuid('222abc0c-fc8e-4a1d-a4b0-d345cafada09'))) {
                    $newResult[] = $item;
                }
            }
            $result = $newResult;
        }

        return new ListTicketTypeDto(
            $result
        );
    }
}
