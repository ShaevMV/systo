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
        return new AccountGetListResponse($this->repositories->getList(
            $accountGetListQuery->getAccountGetListFilter(),
            $accountGetListQuery->getOrderBy(),
        ));
    }
}
