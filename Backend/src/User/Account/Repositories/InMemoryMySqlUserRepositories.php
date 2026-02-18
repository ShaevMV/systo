<?php

declare(strict_types=1);

namespace Tickets\User\Account\Repositories;

use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Hash;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\FilterOperator;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\User\Account\Application\GetList\AccountGetListQuery;
use Tickets\User\Account\Dto\AccountDto;
use Tickets\User\Account\Dto\UserInfoDto;

final class InMemoryMySqlUserRepositories implements UserRepositoriesInterface
{
    public function __construct(
        private User $model
    )
    {
    }

    /**
     * @throws Throwable
     */
    public function create(
        AccountDto $accountDto,
        string     $password
    ): bool
    {

        try {
            DB::beginTransaction();
            $this->model::insert(
                array_merge(
                    $accountDto->toArray(),
                    ['password' => Hash::make($password)],
                    [
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
                    ]
                )
            );
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function findAccountByEmail(string $email): ?UserInfoDto
    {
        if ($findAccount = $this->model::whereEmail($email)->first()?->toArray()) {
            return UserInfoDto::fromState(
                $findAccount
            );
        }

        return null;
    }

    public function findAccountById(Uuid $id): ?UserInfoDto
    {
        if ($findAccount = $this->model::whereId($id->value())->first()?->toArray()) {
            return UserInfoDto::fromState(
                $findAccount
            );
        }

        return null;
    }

    public function getList(Filters $filters): array
    {
        $builder = $this->model::orderBy('created_at', 'DESC');
        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->value()->value()
                );
            }
        }

        $result = [];
        foreach ($builder->get()->toArray() as $item) {
            $result[] = UserInfoDto::fromState($item);
        }

        return $result;
    }
}
