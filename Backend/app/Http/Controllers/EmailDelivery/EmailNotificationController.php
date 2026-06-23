<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmailDelivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tickets\EmailDelivery\Application\QrEmailIntake;

/**
 * S2S-приём писем от витрины qr (Ф4): qr шлёт «отправь письмо <event> на <email> с <vars>».
 * Для не-заказных писем (регистрация, сброс пароля и т.п.), инициированных на витрине.
 * Канал закрыт сервисным ключом qr (middleware qr.ingest).
 *
 * Логика приёма (валидация event/email, идемпотентность по external_id, выбор slug,
 * отправка через MailDispatcher) вынесена в QrEmailIntake — общая с AMQP-консьюмером
 * (routing key qr.email.send), чтобы не дублировать.
 */
class EmailNotificationController extends Controller
{
    public function send(Request $request, QrEmailIntake $intake): JsonResponse
    {
        $result = $intake->ingest($request->toArray());

        return match ($result['status']) {
            'invalid' => response()->json(
                ['success' => false, 'message' => $result['message'] ?? 'Ошибка'],
                422,
            ),
            'duplicate' => response()->json(
                ['success' => true, 'message' => $result['message'] ?? 'Уже принято ранее (идемпотентно)'],
            ),
            default => response()->json([
                'success' => true,
                'email_id' => $result['email_id'] ?? null,
                'message' => 'Письмо принято',
            ]),
        };
    }
}
