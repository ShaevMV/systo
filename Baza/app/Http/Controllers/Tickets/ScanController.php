<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ScanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function scanPage(): View
    {
        return view('tickets.scan');
    }
}
