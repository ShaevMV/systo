<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Application\GetList\AccountGetListFilter;
use Tickets\User\Account\Application\GetList\AccountGetListQuery;
use Tickets\User\Account\Dto\UserInfoDto;
use Tickets\User\Account\Helpers\AccountRoleHelper;


class AccountController extends Controller
{
    /**
     * @throws JsonException
     */
    public function getList(
        Request            $request,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'list' => $accountApplication->getList(
                new AccountGetListQuery(
                    AccountGetListFilter::fromState($request->toArray()['filter']),
                    Order::fromState($request->toArray()['orderBy'])
                )
            )->toArray(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string             $email,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'account' => $accountApplication->getUserByEmail($email)->toArray(),
        ]);
    }

    public function edit(
        string             $id,
        Request            $request,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return response()->json([
            'success' => $accountApplication->edit(
                new Uuid($id),
                UserInfoDto::fromState($request->toArray())
            ),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function changeRole(
        string             $id,
        Request            $request,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return response()->json([
            'success' => $accountApplication->changeRole(
                new Uuid($id),
                $request->get('role'),
            ),
        ]);
    }
}
