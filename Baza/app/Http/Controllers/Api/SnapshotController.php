<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Tickets\Applications\Snapshot\GetSnapshotQuery;
use Baza\Tickets\Applications\Snapshot\SnapshotApplication;
use Carbon\Carbon;
use DomainException;
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
 * TD-48 (за флагом baza.festival_isolation): снимок СТРОГО по фестивалю ОТКРЫТОЙ СМЕНЫ
 * сотрудника — клиентский festival_id игнорируется (нельзя выкачать чужой фестиваль).
 *
 * Ответ:
 *   { success, festival_id, festival_name, items[], next_after_id, has_more, count, server_time }
 * `server_time` — отметка начала выборки, клиент шлёт её как `since` для следующей дельты.
 */
class SnapshotController extends Controller
{
    public function __construct(
        private readonly SnapshotApplication $application,
        private readonly ChangesRepositoryInterface $changes,
        private readonly GetCurrentChanges $getCurrentChanges,
        private readonly FestivalRepositoryInterface $festivals,
    ) {}

    public function index(Request $request): JsonResponse
    {
        // Отметку времени берём ДО выборки — чтобы следующая дельта не пропустила
        // строки, изменённые во время запроса (возможен повторный показ, дедуп на клиенте).
        $serverTime = Carbon::now()->toIso8601String();

        $since = $this->normalizeSince($request->query('since'));
        $afterId = (int) $request->query('after_id', 0);
        $limit = (int) $request->query('limit', 0);

        // Фестиваль снимка: при изоляции — СТРОГО фестиваль смены (клиентский festival_id
        // игнорируется). Нет смены/фестиваля → ПУСТОЙ снимок (НЕ отдаём дефолтный фестиваль —
        // иначе сотрудник без смены выкачал бы на устройство чужие ПДн).
        if ($this->isolationOn()) {
            $festivalId = $this->currentShiftFestivalId();
            if ($festivalId === null) {
                return response()->json([
                    'success' => true,
                    'server_time' => $serverTime,
                    'festival_id' => null,
                    'festival_name' => null,
                    'items' => [],
                    'next_after_id' => $afterId,
                    'has_more' => false,
                    'count' => 0,
                ]);
            }
        } else {
            $festivalId = $this->nullableString($request->query('festival_id'));
        }

        try {
            $page = $this->application->get(
                new GetSnapshotQuery($festivalId, $since, $afterId, $limit),
            );

            // Фактический фестиваль снимка (для офлайн-индикатора «снимок фестиваля X» на клиенте).
            $resolvedFestival = ($festivalId !== null && $festivalId !== '')
                ? $festivalId
                : (string) config('baza.default_festival_id');

            return response()->json([
                'success' => true,
                'server_time' => $serverTime,
                'festival_id' => $resolvedFestival,
                'festival_name' => $this->festivals->nameFor($resolvedFestival),
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

    /** Включена ли строгая изоляция КПП по фестивалю смены (TD-48). */
    private function isolationOn(): bool
    {
        return (bool) config('baza.festival_isolation');
    }

    /** festival_id текущей открытой смены сотрудника или null (смены нет). */
    private function currentShiftFestivalId(): ?string
    {
        try {
            $changeId = $this->getCurrentChanges->getId((int) \Auth::id());
        } catch (DomainException) {
            return null;
        }

        return $this->changes->festivalIdForChange($changeId);
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
