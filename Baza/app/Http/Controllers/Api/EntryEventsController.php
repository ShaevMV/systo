<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Tickets\Applications\EntryEvents\EntryEventsApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Дренаж офлайн-намерений впуска в журнал (Ф5, PR-8): POST /api/entry-events.
 *
 * Сессионная auth персонала (web-группа). Телефон сливает накопленные офлайн намерения
 * пачкой; смена берётся по залогиненному сотруднику (Auth::id()), НЕ из тела. Идемпотентно
 * по client_op_id. Ответ: per-event статус (entered/duplicate/revoked/already/error).
 *
 * До этого PR КПП должен работать строго на ОДНОМ устройстве (нет журнала → возможен
 * двойной впуск). Этот журнал — гейт мульти-устройства (реш. встречи #14).
 */
class EntryEventsController extends Controller
{
    public function __construct(
        private readonly EntryEventsApplication $application,
        private readonly GetCurrentChanges $getCurrentChanges,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $events = $request->input('events', []);
        if (! is_array($events)) {
            $events = [];
        }

        try {
            $changeId = $this->getCurrentChanges->getId((int) \Auth::id());
            $results = $this->application->ingestBatch($events, $changeId);

            return response()->json(['success' => true, 'results' => $results]);
        } catch (Throwable $e) {
            // Напр. нет открытой смены у сотрудника — 422, без сырого текста наружу не светим лишнего.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
