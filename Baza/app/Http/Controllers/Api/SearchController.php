<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Applications\Search\SearchService;
use Baza\Tickets\Services\TicketPiiFilter;
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
 * Ответ: { success, groups: { electron:[...], spisok:[...], live:[...], auto:[...],
 *          drug:[...], ticket_search:[...] } } — как toArray() Blade-страницы.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService,
        private readonly RolePermissionRepositoryInterface $rolePermissions,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['success' => true, 'groups' => []]);
        }

        try {
            $groups = $this->searchService->find($q)->toArray();

            // ПДн в результатах поиска — только при праве ticket.pii (Шаг 3).
            $canViewPii = $this->canViewPii();
            foreach ($groups as $type => $items) {
                $groups[$type] = array_map(
                    static fn (array $item): array => TicketPiiFilter::apply($item, $canViewPii),
                    $items,
                );
            }

            return response()->json(['success' => true, 'groups' => $groups]);
        } catch (Throwable) {
            // Текст исключения наружу не отдаём — обобщённое сообщение.
            return response()->json(['success' => false, 'message' => 'Ошибка поиска'], 500);
        }
    }

    private function canViewPii(): bool
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        return $this->rolePermissions->can($role, ShiftPermission::TICKET_PII);
    }
}
