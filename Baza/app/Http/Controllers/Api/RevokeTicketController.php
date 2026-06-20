<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Blacklist\BlacklistApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Приём отзыва билета от org (Ф5, PR-6, B6): POST /api/baza/ingest/revoke.
 *
 * S2S-канал (middleware baza.ingest, заголовок X-Baza-Token). Закрывает дыру
 * «отмена/возврат» (реш. встречи #8): при отмене заказа org шлёт сюда → билет
 * попадает в чёрный список → блокируется на КПП даже офлайн. Идемпотентно.
 *
 * Контракт: { ticket_uuid?, kilter?, festival_id?, reason? } — нужен хотя бы uuid ИЛИ kilter.
 */
class RevokeTicketController extends Controller
{
    public function __construct(
        private readonly BlacklistApplication $application,
    ) {}

    public function revoke(Request $request): JsonResponse
    {
        $uuid = $this->nullableString($request->input('ticket_uuid'));
        $kilter = $request->input('kilter');
        $kilter = is_numeric($kilter) ? (int) $kilter : null;
        $festivalId = $this->nullableString($request->input('festival_id'));
        $reason = $this->nullableString($request->input('reason'));

        if ($uuid === null && $kilter === null) {
            return response()->json([
                'success' => false,
                'message' => 'Нужен ticket_uuid или kilter',
            ], 422);
        }

        try {
            $this->application->revoke($uuid, $kilter, $festivalId, $reason);

            return response()->json(['success' => true]);
        } catch (Throwable $e) {
            // Сырой текст исключения наружу не отдаём (детали — в лог).
            Log::error('revoke failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось отозвать билет'], 500);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
