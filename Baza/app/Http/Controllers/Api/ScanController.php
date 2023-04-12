<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Search\SearchEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine,
        private EnterTicket  $enterTicket,
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
        try {
            $this->enterTicket->skip(
                $request->get('type'),
                (int)$request->get('id'),
                (int)$request->get('user_id'),
            );
            return response()->json('OK');
        } catch (\Throwable $e) {
            return response()->json($e->getMessage(), 422);
        }

    }
}
