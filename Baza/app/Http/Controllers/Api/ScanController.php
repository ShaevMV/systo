<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Scan\SearchEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScanController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine,
        private EnterTicket  $enterTicket,
        private GetCurrentChanges $getCurrentChanges,
        private AddTicketsInReport $addTicketsInReport,
    )
    {
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $link = $request->get('search');
            return response()->json(
                $this->searchEngine->get($link)->toArray()
            );
        } catch (\DomainException|\InvalidArgumentException $exception) {
            return response()->json($exception->getMessage(), 422);
        }
    }

    public function enter(Request $request): JsonResponse
    {
        try {
            $changeId = $this->getCurrentChanges->getId((int)$request->get('user_id'));
            $this->addTicketsInReport->increment($changeId, $request->get('type'));
            $this->enterTicket->skip(
                $request->get('type'),
                (int)$request->get('id'),
                $changeId,
            );
            return response()->json('OK');
        } catch (\Throwable $e) {
            return response()->json($e->getMessage(), 422);
        }
    }
}
