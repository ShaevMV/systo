<?php

declare(strict_types=1);

namespace App\Http\Controllers\TicketsOrder;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Tickets\Ticket\Live\Service\TicketLiveService;

class LiveTicket extends Controller
{
    public function getNumber(
        string $cash
    ): JsonResponse
    {
        return response()->json([
            'success' => true,
            'number' => TicketLiveService::decrypt($cash),
        ]);
    }
}
