<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changes;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\Report\ReportForChanges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\Utils\JsonException;

class ChangesController extends Controller
{
    public function __construct(
        private ReportForChanges $changes
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function report(): View
    {
        $report = $this->changes->getReport();

        return view('change.index', [
            'report' => $report->getReportList()
        ]);
    }

    public function addChange(Request $request): RedirectResponse
    {
        \Redirect::route('changes.report');
    }
}
