<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\ShiftSchedule\Applications\GetMySchedule\GetMySchedule;
use Illuminate\Http\JsonResponse;

/**
 * Личное расписание сотрудника (PR-A): GET /api/my-schedule.
 *
 * Доступ — ТОЛЬКО middleware('auth') (без shift.compose): рядовой охранник/билетёр
 * должен видеть СВОЁ расписание. Отдаёт открытые/будущие смены, где он в составе.
 * БД только в репозитории, чтение через QueryBus (GetMySchedule).
 */
class MyScheduleController extends Controller
{
    public function __construct(
        private readonly GetMySchedule $getMySchedule,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'schedules' => $this->getMySchedule->get((int) \Auth::id()),
        ]);
    }
}
