<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Snapshot\GetSnapshotQuery;
use Baza\Tickets\Applications\Snapshot\SnapshotApplication;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            // Сырой текст исключения наружу не отдаём (детали — в лог), обобщённое сообщение клиенту.
            Log::error('snapshot failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить снимок',
            ], 500);
        }
    }

    /**
     * Кривой/пустой since → null (полный снимок), а не 500.
     *
     * Нормализуем к таймзоне приложения (Europe/Moscow): updated_at в БД хранится в ней,
     * поэтому `...Z`/иной офсет от клиента приводим к московскому wall-clock — иначе окно
     * дельты сместилось бы на офсет и могло пропустить изменения.
     */
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
