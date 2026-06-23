<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\EntryOutbox\Applications\EntryOutboxApplication;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Festival\Services\FestivalScope;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Scan\SearchEngine;
use Baza\Tickets\Repositories\BlacklistRepositoryInterface;
use Baza\Tickets\Services\TicketPiiFilter;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ScanController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine,
        private EnterTicket $enterTicket,
        private GetCurrentChanges $getCurrentChanges,
        private AddTicketsInReport $addTicketsInReport,
        private EntryOutboxApplication $entryOutbox,
        private BlacklistRepositoryInterface $blacklist,
        private RolePermissionRepositoryInterface $rolePermissions,
        private ChangesRepositoryInterface $changes,
        private FestivalRepositoryInterface $festivals,
        private FestivalScope $festivalScope,
    ) {}

    public function search(Request $request): JsonResponse
    {
        try {
            $link = $request->get('search');
            if (is_null($link)) {
                throw new DomainException('Не опознанный билет просканируй снова!');
            }

            // Изоляция по фестивалю смены (TD-48, за флагом): сканируем ГЛОБАЛЬНО, чтобы
            // найти билет даже чужого фестиваля и показать «жёлтый» вердикт, а не «не найден».
            $shiftFestivalId = null;
            if ($this->isolationOn()) {
                $this->festivalScope->useAny();
                $shiftFestivalId = $this->currentShiftFestivalId();
            }

            $card = $this->searchEngine->get($link)->toArray();

            if ($this->isolationOn()) {
                $card = $this->annotateFestival($card, $shiftFestivalId);
            }

            // ПДн в карточке (телефон/email/коммент) — только при праве ticket.pii (Шаг 3).
            $card = TicketPiiFilter::apply($card, $this->canViewPii());

            return response()->json($card);
        } catch (DomainException|InvalidArgumentException $exception) {
            return response()->json($exception->getMessage(), 422);
        } finally {
            // Не оставляем режим scope «протекать» на следующий запрос (singleton на запрос).
            $this->festivalScope->reset();
        }
    }

    /** Видит ли текущий сотрудник полную карточку (ПДн). administrator — суперроль. */
    private function canViewPii(): bool
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        return $this->rolePermissions->can($role, ShiftPermission::TICKET_PII);
    }

    /**
     * Впуск гостя на КПП. Порядок шагов важен:
     *   1) по сотруднику (Auth::id() — из сессии, НЕ из тела запроса) находим id его
     *      ТЕКУЩЕЙ открытой смены (getCurrentChanges);
     *   2) (TD-48, за флагом) скоупим выборку билета фестивалём смены — билет ЧУЖОГО
     *      фестиваля просто не найдётся в skip() → впуск заблокирован. Право
     *      entry.override_festival + override=1 снимает фильтр (мультифест/ошибки каталога);
     *   3) помечаем билет впущенным (skip): change_id = id смены, date_change = now.
     *      skip() бросает исключение, если билет НЕ найден или УЖЕ был пропущен —
     *      серверная защита от повторного впуска (не только фронт);
     *   4) только при успешном skip — +1 к счётчику прошедших билетов в отчёте смены.
     *
     * Маршрут защищён middleware('auth') в routes/web.php (web-группа: сессия + CSRF).
     */
    public function enter(Request $request): JsonResponse
    {
        try {
            // B6: отозванный билет не пускаем даже онлайн (defense-in-depth к клиентскому blacklist).
            if ($this->blacklist->isRevoked(null, (int) $request->get('id'))) {
                throw new DomainException('Билет отозван');
            }

            $changeId = $this->getCurrentChanges->getId((int) \Auth::id());

            if ($this->isolationOn()) {
                $override = $request->boolean('override') && $this->canOverrideFestival();
                if ($override) {
                    // Впуск билета ДРУГОГО фестиваля по праву (старший смены): скоупим СТРОГО
                    // фестивалём билета (из тела — его прислал клиент по карточке скана), чтобы
                    // не словить коллизию kilter между фестивалями; нет festival_id → глобально.
                    $targetFestival = $request->get('festival_id');
                    is_string($targetFestival) && $targetFestival !== ''
                        ? $this->festivalScope->useFestival($targetFestival)
                        : $this->festivalScope->useAny();
                } else {
                    $shiftFestivalId = $this->changes->festivalIdForChange($changeId);
                    // fail-closed: festival_id смены не задан (легаси/ручная смена) → НЕ молчаливый
                    // дефолт, а блок (билет не найдётся в skip), чтобы не впустить по дефолтному фесту.
                    $shiftFestivalId !== null
                        ? $this->festivalScope->useFestival($shiftFestivalId)
                        : $this->festivalScope->useNone();
                }
            }

            $this->enterTicket->skip(
                $request->get('type'),
                (int) $request->get('id'),
                $changeId,
            );

            $this->addTicketsInReport->increment($changeId, $request->get('type'));

            // Ф4: фиксируем факт входа в outbox для вебхука Baza→org (best-effort, впуск не падает).
            $this->entryOutbox->record($request->get('type'), (int) $request->get('id'), $changeId);

            return response()->json('OK');
        } catch (Throwable $e) {
            return response()->json($e->getMessage(), 422);
        } finally {
            $this->festivalScope->reset();
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
     * Размечает карточку скана фестивальным контекстом: имена фестиваля билета и смены +
     * флаг mismatch (билет ДРУГОГО фестиваля). live/parking без festival_id → mismatch не ставим.
     *
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function annotateFestival(array $card, ?string $shiftFestivalId): array
    {
        $ticketFestivalId = $card['festival_id'] ?? null;

        $card['shift_festival_id'] = $shiftFestivalId;
        $card['shift_festival_name'] = $shiftFestivalId !== null ? $this->festivals->nameFor($shiftFestivalId) : null;
        $card['ticket_festival_name'] = $ticketFestivalId !== null ? $this->festivals->nameFor((string) $ticketFestivalId) : null;
        $card['festival_mismatch'] = $shiftFestivalId !== null
            && $ticketFestivalId !== null
            && (string) $ticketFestivalId !== $shiftFestivalId;

        return $card;
    }

    /** Может ли сотрудник впустить билет ДРУГОГО фестиваля (право entry.override_festival). */
    private function canOverrideFestival(): bool
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        return $this->rolePermissions->can($role, ShiftPermission::ENTRY_OVERRIDE_FESTIVAL);
    }
}
