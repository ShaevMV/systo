<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Search\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService,
        private EnterTicket   $enterTicket,
        private GetCurrentChanges $getCurrentChanges,
        private AddTicketsInReport $addTicketsInReport,
    )
    {
        $this->middleware('auth');
    }


    public function searchPage(Request $request): View
    {
        $result = !is_null($request->get('q')) ? $this->searchService->find($request->get('q'))->toArray() : [];

        return view('tickets.search', [
            'result' => $result,
            'q' => $request->get('q'),
            'tab' => $request->get('tab'),
        ]);
    }


    /**
     * @throws \Throwable
     */
    public function enterForTable(Request $request): RedirectResponse
    {
        $changeId = $this->getCurrentChanges->getId((int)\Auth::id());
        $this->addTicketsInReport->increment($changeId, $request->get('type'));

        $this->enterTicket->skip(
            $request->get('type'),
            (int)$request->get('id'),
            $changeId,
        );

        return \Redirect::route('tickets.search', [
            'q' => $request->get('q'),
            'tab' => $request->get('type'),
        ]);
    }
}
