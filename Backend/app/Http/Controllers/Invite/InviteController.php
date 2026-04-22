<?php

declare(strict_types=1);

namespace App\Http\Controllers\Invite;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Service\InviteLinkService;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class InviteController extends Controller
{

    public function getInviteLink(Request $request, InviteLinkService $inviteLinkService): JsonResponse
    {
        if (!$userId = $request->user()?->id) {
            return response()->json([
                'message' => 'страница доступна только для зарегистрированного пользователя',
                'link' => null
            ]);
        }

        if ($link = $inviteLinkService->getLink(new Uuid($userId), $request->user()->role)) {
            return response()->json([
                'message' => 'Вам доступна ссылка для приглашение друга',
                'link' => $link
            ]);
        }

        return response()->json([
            'message' => 'Формирование ссылки-приглашения будет доступно после одобрения хотя бы одного из ваших заказов',
            'link' => null
        ]);
    }

    public function isCorrectInviteLink(string $userId, InviteLinkService $inviteLinkService): JsonResponse
    {
        $user = User::where('id', '=', $userId)->first();

        if (empty($user->email)) {
            return response()->json([
                'success' => false
            ]);
        }
        return response()->json([
            'success' => $inviteLinkService->isPaidOrderByUserId(
                new Uuid($userId),
                $user->email,
                $user->role,
            ),
        ]);
    }
}
