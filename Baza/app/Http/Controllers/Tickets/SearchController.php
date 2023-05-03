<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Baza\Tickets\Applications\Search\SearchService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private SearchService $searchService
    )
    {
        $this->middleware('auth');
    }


    public function searchPage(Request $request): View
    {
        $result = !is_null($request->get('q')) ? $this->searchService->find($request->get('q'))->toArray(): [];

        return view('tickets.search',[
            'result' => $result
        ]);
    }
}
