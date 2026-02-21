<?php

namespace Tickets\User\Account\Application\GetList;

use App\Models\User;
use Shared\Domain\Bus\Query\QueryHandler;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Tickets\User\Account\Dto\UserInfoDto;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;
use Tickets\User\Account\Response\AccountGetListResponse;

class AccountGetListQueryHandler implements QueryHandler
{
    public function __construct(
        private UserRepositoriesInterface $repositories
    )
    {
    }

    /**
     * @param AccountGetListQuery $accountGetListQuery
     * @return UserInfoDto[]
     */
    public function __invoke(AccountGetListQuery $accountGetListQuery): AccountGetListResponse
    {
        $filter = Filters::fromValues($this->getFilterValues($accountGetListQuery));
        return new AccountGetListResponse($this->repositories->getList($filter));
    }

    private function getFilterValues(AccountGetListQuery $filterQuery): array
    {
        return [
            // email
            [
                'field' => User::TABLE . '.email',
                'operator' => FilterOperator::LIKE,
                'value' => '%'.$filterQuery->getEmail().'%',
            ],
            // name
            [
                'field' => User::TABLE . '.name',
                'operator' => FilterOperator::LIKE,
                'value' => '%'.$filterQuery->getName().'%',
            ],
            // types_of_payment_id
            [
                'field' => User::TABLE . '.types_of_payment_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getCity(),
            ],
            [
                'field' => User::TABLE . '.city',
                'operator' => FilterOperator::LIKE,
                'value' => '%'.$filterQuery->getCity().'%',
            ],
            [
                'field' => User::TABLE . '.phone',
                'operator' => FilterOperator::LIKE,
                'value' => '%'.$filterQuery->getPhone().'%',
            ],
            [
                'field' => User::TABLE . '.ticket_type_id',
                'operator' => FilterOperator::EQUAL,
                'value' => $filterQuery->getRole(),
            ],
        ];
    }
}
