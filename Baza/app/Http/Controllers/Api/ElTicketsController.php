<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ElTicketsController
{
    public function createTickets(Request $request): JsonResponse
    {
        Log::log($request->toArray());

        return response()->json([]);
    }
}
