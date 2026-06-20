<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Ingest\Applications\IngestTicket\IngestTicketApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

/**
 * Приём билета от org через ingest-API (Ф3): POST /api/baza/ingest/ticket.
 *
 * S2S-канал (middleware baza.ingest, заголовок X-Baza-Token). Контракт:
 *   { "target": "el_tickets|spisok_tickets|live_tickets|auto", "ticket": { ...поля цели... } }
 *
 * Идемпотентно по естественному ключу цели (org может ретраить). Ответ:
 *   200 { success: true }  — билет записан;
 *   200 { success: false } — не применено (напр. live-номера ещё нет) → org откатится на прямую запись;
 *   422 — битый контракт (неизвестная цель / нет ключа билета);
 *   401 — нет/неверный X-Baza-Token (middleware).
 */
class IngestTicketController extends Controller
{
    public function __construct(
        private readonly IngestTicketApplication $application,
    ) {}

    public function ticket(Request $request): JsonResponse
    {
        $target = (string) $request->input('target', '');
        $ticket = $request->input('ticket', []);
        if (! is_array($ticket)) {
            $ticket = [];
        }
        // Опциональный богатый блок для поискового индекса (Ф-rich). Нет → fallback на ticket.
        $search = $request->input('search', []);
        if (! is_array($search)) {
            $search = [];
        }

        try {
            $applied = $this->application->ingest($target, $ticket, $search);

            return response()->json([
                'success' => $applied,
                'target' => $target,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            // Реальная ошибка записи — 500, чтобы org откатился на прямую запись и/или ретрай.
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
