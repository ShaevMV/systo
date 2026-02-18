<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nette\Utils\JsonException;
use Tickets\User\Account\Application\AccountApplication;
use Tickets\User\Account\Application\GetList\AccountGetListQuery;


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
}
