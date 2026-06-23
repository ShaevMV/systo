<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\OpenAndClose\OpenAndCloseChanges;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Festival\Services\FestivalForShiftResolver;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Repositories\UserRepositoryInterface;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Управление сменами из нового PWA (Шаг 6): /api/shifts.
 *
 * Логику не переписываем — те же SaveChange / OpenAndCloseChanges (CQRS), БД только в репо.
 * Изоляция (реш. владельца): начальник смены видит/закрывает ТОЛЬКО свою смену; administrator — все.
 * Доступ — permission:shift.compose (список/создание/состав), shift.close (закрытие).
 */
class ShiftController extends Controller
{
    public function __construct(
        private readonly ChangesRepositoryInterface $changes,
        private readonly SaveChange $saveChange,
        private readonly OpenAndCloseChanges $openAndClose,
        private readonly UserRepositoryInterface $users,
        private readonly FestivalForShiftResolver $festivalResolver,
    ) {}

    /** Открытые смены: admin — все, начальник — только свои. */
    public function index(): JsonResponse
    {
        $chiefFilter = $this->isAdmin() ? null : (int) \Auth::id();

        return response()->json([
            'success' => true,
            'is_admin' => $this->isAdmin(),
            'shifts' => $this->changes->listOpen($chiefFilter),
        ]);
    }

    /** Список сотрудников для выбора состава смены. */
    public function users(): JsonResponse
    {
        return response()->json(['success' => true, 'users' => $this->users->list()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'members' => 'required|array|min:1',
            'members.*' => 'integer',
            'chief_id' => 'nullable|integer',
            'festival_id' => 'nullable|string',
        ]);

        $members = array_values(array_unique(array_map('intval', $data['members'])));

        // Начальник создаёт ТОЛЬКО свою смену (chief = он сам); administrator выбирает начальника.
        if ($this->isAdmin()) {
            $chiefId = isset($data['chief_id']) ? (int) $data['chief_id'] : null;
            if ($chiefId === null) {
                return response()->json(['success' => false, 'message' => 'Укажите начальника смены'], 422);
            }
        } else {
            $chiefId = (int) \Auth::id();
        }

        // Инвариант Ф2: начальник входит в состав.
        if (! in_array($chiefId, $members, true)) {
            $members[] = $chiefId;
        }

        // Фестиваль смены (TD-48): авто-выбор единственного / обязателен при нескольких.
        try {
            $festivalId = $this->festivalResolver->resolve($data['festival_id'] ?? null);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            $this->saveChange->save($members, Carbon::now(), null, $chiefId, $festivalId);

            return response()->json(['success' => true, 'message' => 'Смена создана']);
        } catch (Throwable $e) {
            Log::error('shift create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось создать смену'], 500);
        }
    }

    public function close(int $id): JsonResponse
    {
        if (! $this->changes->exists($id)) {
            return response()->json(['success' => false, 'message' => 'Смена не найдена'], 404);
        }

        // Изоляция: начальник закрывает только свою смену; administrator — любую.
        if (! $this->isAdmin() && $this->changes->getChiefId($id) !== (int) \Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Можно закрыть только свою смену'], 403);
        }

        try {
            $this->openAndClose->close($id);

            return response()->json(['success' => true, 'message' => 'Смена закрыта']);
        } catch (Throwable $e) {
            Log::error('shift close failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось закрыть смену'], 500);
        }
    }

    private function isAdmin(): bool
    {
        $u = \Auth::user();

        return ShiftRole::fromUser((bool) $u->is_admin, $u->role) === ShiftRole::ADMINISTRATOR;
    }
}
