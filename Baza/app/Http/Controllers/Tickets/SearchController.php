<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Festival\Services\FestivalScope;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Search\SearchService;
use Baza\Tickets\Services\TicketPiiFilter;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Redirect;
use Throwable;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private EnterTicket   $enterTicket,
        private GetCurrentChanges $getCurrentChanges,
        private AddTicketsInReport $addTicketsInReport,
        private RolePermissionRepositoryInterface $rolePermissions,
        private FestivalScope $festivalScope,
        private ChangesRepositoryInterface $changes,
    )
    {
        $this->middleware('auth');
    }


    public function searchPage(Request $request): View
    {
        $result = [];
        if (! is_null($request->get('q'))) {
            try {
                // Изоляция по фестивалю смены (TD-48, за флагом) — как Api\SearchController.
                if ($this->isolationOn()) {
                    $shiftFestivalId = $this->currentShiftFestivalId();
                    if ($shiftFestivalId !== null) {
                        $this->festivalScope->useFestival($shiftFestivalId);
                    } else {
                        $this->festivalScope->useNone(); // fail-closed: нет смены → пусто
                    }
                }

                $result = $this->searchService->find($request->get('q'))->toArray();
            } finally {
                $this->festivalScope->reset();
            }

            // ПДн (телефон/email/коммент) — только при праве ticket.pii (Шаг 3). Зеркало Api\SearchController:
            // раньше старый Blade показывал ПДн всем сотрудникам мимо фильтра (152-ФЗ).
            $canViewPii = $this->canViewPii();
            foreach ($result as $type => $items) {
                $result[$type] = array_map(
                    static fn (array $item): array => TicketPiiFilter::apply($item, $canViewPii),
                    $items,
                );
            }
        }

        return view('tickets.search', [
            'result' => $result,
            'q' => $request->get('q'),
            'tab' => $request->get('tab'),
            'error' => $request->get('error'),
        ]);
    }

    /** Видит ли текущий сотрудник ПДн в карточке (право ticket.pii; administrator — суперроль). */
    private function canViewPii(): bool
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        return $this->rolePermissions->can($role, ShiftPermission::TICKET_PII);
    }


    /**
     * @throws Throwable
     */
    public function enterForTable(Request $request): RedirectResponse
    {
        try {
            $changeId = $this->getCurrentChanges->getId((int)\Auth::id());

            // Изоляция (TD-48, за флагом): билет ЧУЖОГО фестиваля не найдётся в skip() → впуск
            // заблокирован. Override на legacy Blade не предусмотрен (он есть в PWA-сканере).
            if ($this->isolationOn()) {
                $shiftFestivalId = $this->changes->festivalIdForChange($changeId);
                if ($shiftFestivalId !== null) {
                    $this->festivalScope->useFestival($shiftFestivalId);
                }
            }

            // Сначала впуск (skip бросит исключение, если билет уже был пропущен/не найден),
            // и только при успехе — +1 в отчёт смены. Иначе повторный впуск накручивал счётчик.
            $this->enterTicket->skip(
                $request->get('type'),
                (int)$request->get('id'),
                $changeId,
            );

            $this->addTicketsInReport->increment($changeId, $request->get('type'));

            return Redirect::route('tickets.search', [
                'q' => $request->get('q'),
                'tab' => $request->get('type'),
            ]);
        } catch (Throwable $exception) {
            return Redirect::route('tickets.search', [
                'q' => $request->get('q'),
                'tab' => $request->get('type'),
                'error' => $exception->getMessage(),
            ]);
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
}
