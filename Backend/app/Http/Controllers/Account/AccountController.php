<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Application\GetList\AccountGetListQuery;
use Tickets\User\Account\Dto\UserInfoDto;


class AccountController extends Controller
{
    /**
     * @throws JsonException
     */
    public function getList(
        Request $request,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return  response()->json([
            'success' => true,
            'accounts' => $accountApplication->getList(
                AccountGetListQuery::fromState($request->toArray())
            )->toArray(),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function getItem(
        string $email,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return  response()->json([
            'success' => true,
            'account' => $accountApplication->getUserByEmail($email)->toArray(),
        ]);
    }

    public function edit(
        string $id,
        Request $request,
        AccountApplication $accountApplication,
    ): JsonResponse
    {
        return  response()->json([
            'success' => $accountApplication->edit(
                new Uuid($id),
                UserInfoDto::fromState($request->toArray())
            ),
        ]);
    }
}
