<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\ShiftSchedule\Applications\CancelSchedule\CancelSchedule;
use Baza\ShiftSchedule\Applications\CreateSchedule\CreateSchedule;
use Baza\ShiftSchedule\Applications\EditSchedule\EditSchedule;
use Baza\ShiftSchedule\Applications\ListSchedules\ListSchedules;
use Baza\ShiftSchedule\Dto\ShiftScheduleDto;
use Baza\ShiftSchedule\Repositories\ShiftScheduleRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * Плановое расписание смен (PR-A): /api/schedules. Составление сетки смен заранее.
 *
 * Доступ — permission:shift.compose (как ShiftController). Изоляция (как у факта смены):
 * начальник создаёт ТОЛЬКО свою плановую смену (chief = он сам); administrator выбирает
 * начальника. БД только в репозитории, логику ведёт Application (CQRS).
 */
class ShiftScheduleController extends Controller
{
    /** Текущий фестиваль (как в SaveChange / репозиториях; кандидат на env, BAZA.md §9). */
    private const FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    public function __construct(
        private readonly ShiftScheduleRepositoryInterface $schedules,
        private readonly CreateSchedule $createSchedule,
        private readonly EditSchedule $editSchedule,
        private readonly CancelSchedule $cancelSchedule,
        private readonly ListSchedules $listSchedules,
    ) {}

    /**
     * Список плановых смен фестиваля для сетки «точки КПП × смены» (фильтры — дата, точка КПП).
     * Сетка ОБЩАЯ для всех составителей (право shift.compose) — не изолируется по начальнику
     * (в отличие от факта смены): план виден целиком, чтобы раскладывать смены по дням.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'festival_id' => 'nullable|string',
            'shift_date' => 'nullable|date',
            'kpp_point' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'is_admin' => $this->isAdmin(),
            'schedules' => $this->listSchedules->list(
                $data['festival_id'] ?? self::FESTIVAL,
                $data['shift_date'] ?? null,
                $data['kpp_point'] ?? null,
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $chiefId = $this->resolveChiefId($data);
        if ($chiefId === null) {
            return response()->json(['success' => false, 'message' => 'Укажите начальника смены'], 422);
        }

        $dto = $this->buildDto(null, $data, $chiefId, 'planned');

        try {
            $this->createSchedule->create($dto);

            return response()->json(['success' => true, 'message' => 'Плановая смена создана']);
        } catch (Throwable $e) {
            Log::error('schedule create failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось создать плановую смену'], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $plan = $this->schedules->find($id);
        if ($plan === null) {
            return response()->json(['success' => false, 'message' => 'Плановая смена не найдена'], 404);
        }
        if (! $this->canManagePlan($plan)) {
            return response()->json(['success' => false, 'message' => 'Можно менять только свою плановую смену'], 403);
        }
        if (($plan['status'] ?? null) === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Отменённую плановую смену изменить нельзя'], 422);
        }

        $data = $this->validatePayload($request);

        $chiefId = $this->resolveChiefId($data);
        if ($chiefId === null) {
            return response()->json(['success' => false, 'message' => 'Укажите начальника смены'], 422);
        }

        $dto = $this->buildDto($id, $data, $chiefId, 'planned');

        try {
            $this->editSchedule->edit($id, $dto);

            return response()->json(['success' => true, 'message' => 'Плановая смена изменена']);
        } catch (Throwable $e) {
            Log::error('schedule update failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось изменить плановую смену'], 500);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $plan = $this->schedules->find($id);
        if ($plan === null) {
            return response()->json(['success' => false, 'message' => 'Плановая смена не найдена'], 404);
        }
        if (! $this->canManagePlan($plan)) {
            return response()->json(['success' => false, 'message' => 'Можно отменить только свою плановую смену'], 403);
        }
        if (($plan['status'] ?? null) === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Плановая смена уже отменена'], 422);
        }

        try {
            $this->cancelSchedule->cancel($id);

            return response()->json(['success' => true, 'message' => 'Плановая смена отменена']);
        } catch (Throwable $e) {
            Log::error('schedule cancel failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Не удалось отменить плановую смену'], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'festival_id' => 'nullable|string',
            'kpp_point' => 'nullable|string|max:255',
            'shift_date' => 'required|date',
            'planned_start' => 'required|date',
            'planned_end' => 'nullable|date',
            'name' => 'nullable|string|max:255',
            'chief_id' => 'nullable|integer',
            'members' => 'required|array|min:1',
            'members.*.user_id' => 'required|integer',
            'members.*.role' => ['nullable', Rule::in(ShiftRole::all())],
        ]);
    }

    /**
     * Начальник смены: administrator берёт из тела (chief_id), начальник — он сам (Auth::id()).
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveChiefId(array $data): ?int
    {
        if ($this->isAdmin()) {
            return isset($data['chief_id']) ? (int) $data['chief_id'] : null;
        }

        return (int) \Auth::id();
    }

    /**
     * Сборка DTO плана: состав + гарантия, что начальник в составе как shift_chief.
     * Контроллер в БД НЕ ходит (Dependency Rule): передаёт роль ЯВНУЮ из формы либо null —
     * мягкий маппинг роли по users.role/is_admin делает репозиторий (syncMembers).
     *
     * @param  array<string, mixed>  $data
     */
    private function buildDto(?int $id, array $data, int $chiefId, string $status): ShiftScheduleDto
    {
        $rawMembers = $data['members'] ?? [];

        $members = [];
        foreach ($rawMembers as $m) {
            $explicitRole = $m['role'] ?? null;
            $members[] = [
                'userId' => (int) $m['user_id'],
                'role' => ($explicitRole !== null && ShiftRole::isValid((string) $explicitRole))
                    ? (string) $explicitRole
                    : null,
            ];
        }

        // Инвариант: начальник входит в состав как shift_chief (репозиторий тоже это гарантирует).
        $members[] = ['userId' => $chiefId, 'role' => ShiftRole::SHIFT_CHIEF];

        return new ShiftScheduleDto(
            id: $id,
            festivalId: $data['festival_id'] ?? self::FESTIVAL,
            kppPoint: $data['kpp_point'] ?? null,
            shiftDate: Carbon::parse($data['shift_date']),
            plannedStart: Carbon::parse($data['planned_start']),
            plannedEnd: isset($data['planned_end']) ? Carbon::parse($data['planned_end']) : null,
            name: $data['name'] ?? null,
            status: $status,
            chiefId: $chiefId,
            members: $members,
        );
    }

    /**
     * Изоляция: начальник управляет только своей плановой сменой; administrator — любой.
     *
     * @param  array<string, mixed>  $plan
     */
    private function canManagePlan(array $plan): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return (int) ($plan['chief_id'] ?? 0) === (int) \Auth::id();
    }

    private function isAdmin(): bool
    {
        $u = \Auth::user();

        return ShiftRole::fromUser((bool) $u->is_admin, $u->role) === ShiftRole::ADMINISTRATOR;
    }
}
