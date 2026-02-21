<?php

declare(strict_types=1);

namespace Tickets\TicketType\Application\GetItem;

use App\Models\Festival\TypesOfPaymentModel;
use App\Models\Ordering\OrderTicketModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\TicketType\Repository\TicketTypeRepositoryInterface;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;
use Tickets\TypesOfPayment\Response\TypesOfPaymentListResponse;

class TicketTypeGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private TicketTypeRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TicketTypeGetItemQuery $query): TicketTypeDto
    {
        return $this->repository->getItem($query->getId());
    }

}
