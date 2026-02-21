<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetItem;

use App\Models\Festival\TypesOfPaymentModel;
use App\Models\Ordering\OrderTicketModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;
use Tickets\TypesOfPayment\Response\TypesOfPaymentListResponse;

class TypesOfPaymentGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TypesOfPaymentGetItemQuery $query): TypesOfPaymentDto
    {
        return $this->repository->getItem($query->getId());
    }

}
