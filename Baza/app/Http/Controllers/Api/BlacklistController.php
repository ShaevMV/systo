<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Blacklist\BlacklistApplication;
use Baza\Tickets\Applications\Blacklist\GetBlacklistQuery;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Синк чёрного списка отозванных билетов для PWA (Ф5, PR-6, B6): GET /api/blacklist.
 *
 * Сессионная auth персонала. БЕЗ ПДн (только uuid/kilter/reason/festival). Телефон
 * тянет blacklist ПРИОРИТЕТНЕЕ снимка: отозванный билет блокируется офлайн (красный экран).
 * Дельта по since + пагинация after_id (как /api/snapshot).
 */
class BlacklistController extends Controller
{
    public function __construct(
        private readonly BlacklistApplication $application,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $serverTime = Carbon::now()->toIso8601String();

        $festivalId = $this->nullableString($request->query('festival_id'));
        $since = $this->normalizeSince($request->query('since'));
        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 0);

        try {
            $page = $this->application->getPage(
                new GetBlacklistQuery($festivalId, $since, $afterId, $limit),
            );

            return response()->json([
                'success' => true,
                'server_time' => $serverTime,
            ] + $page->toArray());
        } catch (Throwable $e) {
            Log::error('blacklist failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось получить список'], 500);
        }
    }

    private function normalizeSince(mixed $since): ?string
    {
        if (! is_string($since) || trim($since) === '') {
            return null;
        }
        try {
            return Carbon::parse($since)->setTimezone(config('app.timezone'))->toDateTimeString();
        } catch (Throwable) {
            return null;
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
