<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetList;

use App\Models\Festival\TypesOfPaymentModel;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;
use Tickets\TypesOfPayment\Response\TypesOfPaymentListResponse;

class TypesOfPaymentGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private TypesOfPaymentRepositoryInterface $repository
    )
    {
    }

    public function __invoke(TypesOfPaymentGetListQuery $query): TypesOfPaymentListResponse
    {
        return new TypesOfPaymentListResponse(
            $this->repository->getList(
               $query->getTypesOfPaymentGetListFilter(),
                $query->getOrderBy(),
            )
        );
    }
}
