<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Search\SearchService;
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
    ) {}

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['success' => true, 'groups' => []]);
        }

        try {
            return response()->json([
                'success' => true,
                'groups' => $this->searchService->find($q)->toArray(),
            ]);
        } catch (Throwable) {
            // Текст исключения наружу не отдаём — обобщённое сообщение.
            return response()->json(['success' => false, 'message' => 'Ошибка поиска'], 500);
        }
    }
}
