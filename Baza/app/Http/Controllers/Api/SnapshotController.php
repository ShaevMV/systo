<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Snapshot\GetSnapshotQuery;
use Baza\Tickets\Applications\Snapshot\SnapshotApplication;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Офлайн-снимок билетов для PWA-сканера (Ф5, PR-3): GET /api/snapshot.
 *
 * Сессионная auth персонала (web-группа + middleware auth, как /api/scan, /api/enter):
 * снимок содержит ФИО гостей — отдаём только залогиненному сотруднику КПП.
 *
 * Контракт запроса (query): festival_id?, since? (ISO/datetime — дельта), after_id? (курсор), limit?.
 * Минимизация ПДн B5: только uuid/kilter/тип/цвет браслета/имя.
 *
 * Ответ:
 *   { success, items[], next_after_id, has_more, count, server_time }
 * `server_time` — отметка начала выборки, клиент шлёт её как `since` для следующей дельты.
 */
class SnapshotController extends Controller
{
    public function __construct(
        private readonly SnapshotApplication $application,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Отметку времени берём ДО выборки — чтобы следующая дельта не пропустила
        // строки, изменённые во время запроса (возможен повторный показ, дедуп на клиенте).
        $serverTime = Carbon::now()->toIso8601String();

        $festivalId = $this->nullableString($request->query('festival_id'));
        $since = $this->normalizeSince($request->query('since'));
        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 0);

        try {
            $page = $this->application->get(
                new GetSnapshotQuery($festivalId, $since, $afterId, $limit),
            );

            return response()->json([
                'success' => true,
                'server_time' => $serverTime,
            ] + $page->toArray());
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /** Кривой/пустой since → null (полный снимок), а не 500. */
    private function normalizeSince(mixed $since): ?string
    {
        if (! is_string($since) || trim($since) === '') {
            return null;
        }

        try {
            return Carbon::parse($since)->toDateTimeString();
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
