<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Search\SearchEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine,
    )
    {

    }

    public function search(Request $request): JsonResponse
    {
        try {
            $link = $request->get('search');
            return response()->json(
                $this->searchEngine->get($link)->toArray());
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json($exception->getMessage(), 422);
        }
    }

    public function enter(Request $request): JsonResponse
    {
        return response()->json($request->toArray());
    }
}
