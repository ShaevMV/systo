<?php

declare(strict_types=1);

namespace App\Http\Controllers\Baza;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;
use Tickets\BazaWebhook\Application\BazaWebhookApplication;

/**
 * Приём вебхука «билет прошёл» от Baza (Ф4): POST /api/v1/baza/ticketEntered.
 *
 * S2S-канал (middleware baza.webhook, заголовок X-Baza-Token). Пишет факт входа в историю
 * (actor_type=baza). Идемпотентно по event_id (повтор ретрая дренажа не дублирует).
 */
class BazaWebhookController extends Controller
{
    public function __construct(
        private readonly BazaWebhookApplication $application,
    ) {}

    public function ticketEntered(Request $request): JsonResponse
    {
        try {
            $recorded = $this->application->recordEntry($request->all());

            return response()->json([
                'success' => true,
                'recorded' => $recorded, // false = идемпотентный повтор
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
