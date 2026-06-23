<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Festival\Applications\ListFestivals\ListFestivals;
use Baza\Festival\Applications\SetActiveForKpp\SetActiveForKpp;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Реестр фестивалей на Vhod (TD-48, PR-1): /api/festivals.
 *
 * Логику не дублируем — те же ListFestivals / SetActiveForKpp (CQRS), БД только в репо.
 * Доступ: выбор фестиваля для смены (`index`) — право shift.compose (как /api/shifts);
 * управление реестром (`registry` / `setActive`) — право festival.manage
 * (по умолчанию только administrator-суперроль).
 */
class FestivalController extends Controller
{
    public function __construct(
        private readonly ListFestivals $listFestivals,
        private readonly SetActiveForKpp $setActiveForKpp,
        private readonly FestivalRepositoryInterface $festivals,
    ) {}

    /**
     * Фестивали для выбора при открытии смены (только active_for_kpp).
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'festivals' => $this->listFestivals->activeForKpp(),
        ]);
    }

    /**
     * Весь реестр фестивалей (для экрана управления).
     */
    public function registry(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'festivals' => $this->listFestivals->all(),
        ]);
    }

    /**
     * Включить/выключить доступность фестиваля для КПП.
     */
    public function setActive(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'active' => 'required|boolean',
        ]);

        if (! $this->festivals->exists($id)) {
            return response()->json(['success' => false, 'message' => 'Фестиваль не найден'], 404);
        }

        try {
            $this->setActiveForKpp->set($id, (bool) $data['active']);

            return response()->json([
                'success' => true,
                'message' => $data['active'] ? 'Фестиваль открыт для КПП' : 'Фестиваль скрыт с КПП',
            ]);
        } catch (Throwable $e) {
            Log::error('festival set-active failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось изменить доступность'], 500);
        }
    }
}
