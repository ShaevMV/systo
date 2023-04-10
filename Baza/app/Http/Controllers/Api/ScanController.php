<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        return response()->json($request->toArray());
    }
}
