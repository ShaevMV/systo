<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function searchPage(): View
    {
        return view('tickets.search');
    }
}
