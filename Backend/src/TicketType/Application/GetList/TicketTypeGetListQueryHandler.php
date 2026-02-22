<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetList;

use App\Models\Festival\TicketTypesModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TicketType\Repository\TicketTypeRepositoryInterface;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

class TicketTypeGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeGetListQuery $query): TicketTypeGetListResponse
    {
        return new TicketTypeGetListResponse(
            $this->repository->getList(
                $query->getFilter(),
                $query->getOrderBy(),
            )
        );
    }
}
