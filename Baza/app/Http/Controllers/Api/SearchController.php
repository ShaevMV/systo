<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Festival\Services\FestivalScope;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Applications\Search\SearchService;
use Baza\Tickets\Services\TicketPiiFilter;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * JSON-поиск билета без QR для PWA-сканера (Ф5, PR-5): GET /api/search?q=.
 *
 * Сессионная auth персонала (web-группа + middleware auth, как /api/scan). Тот же
 * SearchService::find, что и Blade-страница /search — богатый поиск по ФИО/телефону/
 * телеге/госномеру/№ заказа (ticket_search) + поиск по типам. Только ОНЛАЙН (полные
 * поля). Офлайн-поиск идёт по локальному снимку на клиенте (B5: имя/номер).
 *
 * TD-48 (за флагом baza.festival_isolation): результаты ограничены фестивалём ОТКРЫТОЙ
 * СМЕНЫ сотрудника (FestivalScope) — чтобы не показывать гостей чужого события.
 *
 * Ответ: { success, festival_scope?, groups: { electron:[...], spisok:[...], live:[...],
 *          auto:[...], drug:[...], ticket_search:[...] } } — как toArray() Blade-страницы.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly RolePermissionRepositoryInterface $rolePermissions,
        private readonly FestivalScope $festivalScope,
        private readonly ChangesRepositoryInterface $changes,
        private readonly GetCurrentChanges $getCurrentChanges,
        private readonly FestivalRepositoryInterface $festivals,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['success' => true, 'groups' => []]);
        }

        $festivalScopeName = null;

        try {
            // Изоляция по фестивалю смены (TD-48, за флагом): поиск только в своём фестивале.
            if ($this->isolationOn()) {
                $shiftFestivalId = $this->currentShiftFestivalId();
                if ($shiftFestivalId !== null) {
                    $this->festivalScope->useFestival($shiftFestivalId);
                    $festivalScopeName = $this->festivals->nameFor($shiftFestivalId);
                } else {
                    // fail-closed: нет смены/фестиваля → пустой результат, а не дефолтный фестиваль.
                    $this->festivalScope->useNone();
                }
            }

            $groups = $this->searchService->find($q)->toArray();

            // ПДн в результатах поиска — только при праве ticket.pii (Шаг 3).
            $canViewPii = $this->canViewPii();
            foreach ($groups as $type => $items) {
                $groups[$type] = array_map(
                    static fn (array $item): array => TicketPiiFilter::apply($item, $canViewPii),
                    $items,
                );
            }

            $payload = ['success' => true, 'groups' => $groups];
            if ($festivalScopeName !== null) {
                $payload['festival_scope'] = $festivalScopeName;
            }

            return response()->json($payload);
        } catch (Throwable) {
            // Текст исключения наружу не отдаём — обобщённое сообщение.
            return response()->json(['success' => false, 'message' => 'Ошибка поиска'], 500);
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

    private function canViewPii(): bool
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        return $this->rolePermissions->can($role, ShiftPermission::TICKET_PII);
    }
}
