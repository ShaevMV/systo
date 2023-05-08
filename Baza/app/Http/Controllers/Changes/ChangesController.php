<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changes;

use App\Http\Controllers\Controller;
use App\Models\User;
use Baza\Changes\Applications\OpenAndClose\OpenAndCloseChanges;
use Baza\Changes\Applications\Report\ReportForChanges;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use phpDocumentor\Reflection\Types\Integer;
use Redirect;
use Throwable;

class ChangesController extends Controller
{
    public function __construct(
        private ReportForChanges           $changes,
        private SaveChange                 $saveChange,
        private ChangesRepositoryInterface $repository,
        private OpenAndCloseChanges $openAndCloseChanges,
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

    public function viewAddChange(User $user,?int $id = null): View
    {
        if ($id > 0) {
            $findChange = $this->repository->get($id);
            $findChange['user_id'] = Json::decode($findChange['user_id']);
            $findChange['start'] = Carbon::parse($findChange['start'])->format('Y-m-d H:i');
        }

        return view('change.add', [
            'users' => $user->all(),
            'findChange' => $findChange ?? [],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function save(Request $request): RedirectResponse
    {
        $id = $request->get('id') === null ? null : (int)$request->get('id');

        $this->saveChange->save(
            $request->get('compound'),
            Carbon::parse($request->get('start')),
            $id,
        );

        return Redirect::route('changes.report');
    }

    public function close(Request $request): RedirectResponse
    {
        $id = (int)$request->get('id');
        $this->openAndCloseChanges->close($id);

        return Redirect::route('changes.report');
    }

    public function remove(Request $request): RedirectResponse
    {
        $id = (int)$request->get('id');
        $this->repository->remove($id);

        return Redirect::route('changes.report');
    }
}
