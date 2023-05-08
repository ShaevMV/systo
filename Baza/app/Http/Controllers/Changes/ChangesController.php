<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changes;

use App\Http\Controllers\Controller;
use App\Models\User;
use Baza\Changes\Applications\Report\ReportForChanges;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\Utils\JsonException;
use Redirect;

class ChangesController extends Controller
{
    public function __construct(
        private ReportForChanges $changes,
        private SaveChange $saveChange,
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

    public function viewAddChange(User $user): View
    {
        return view('change.add', [
            'users' => $user->all()
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function save(Request $request): RedirectResponse
    {
        $this->saveChange->save(
            $request->get('compound'),
            Carbon::parse($request->get('start')),
        );

        return \Redirect::route('changes.report');
    }
}
