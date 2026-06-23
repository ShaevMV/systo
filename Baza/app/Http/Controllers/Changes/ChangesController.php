<?php

declare(strict_types=1);

namespace App\Http\Controllers\Changes;

use App\Http\Controllers\Controller;
use App\Models\User;
use Baza\Changes\Applications\OpenAndClose\OpenAndCloseChanges;
use Baza\Changes\Applications\Report\ReportForChanges;
use Baza\Changes\Applications\SaveChange\SaveChange;
use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Festival\Applications\ListFestivals\ListFestivals;
use Baza\Festival\Repositories\FestivalRepositoryInterface;
use Baza\Festival\Services\FestivalForShiftResolver;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Redirect;
use Throwable;

class ChangesController extends Controller
{
    public function __construct(
        private ReportForChanges           $changes,
        private SaveChange                 $saveChange,
        private ChangesRepositoryInterface $repository,
        private OpenAndCloseChanges $openAndCloseChanges,
        private ListFestivals $festivalList,
        private FestivalForShiftResolver $festivalResolver,
        private FestivalRepositoryInterface $festivals,
    )
    {
    }

    /**
     * Отчёт смен фестиваля (TD-48). Фестиваль: явный `?festival_id` (выбор admin) →
     * при изоляции фестиваль смены начальника → иначе дефолтный.
     */
    public function report(Request $request): View
    {
        $festivalId = $this->resolveReportFestival($request);
        $report = $this->changes->getReport($festivalId);

        return view('change.index', [
            'report' => $report->getReportList(),
            'total' => $report->getReportTotalDto()->toArray(),
            'festivals' => $this->festivalList->all(),
            'festivalId' => $festivalId,
            'festivalName' => $this->festivals->nameFor($festivalId),
        ]);
    }

    /** Какой фестиваль показывать в отчёте: явный выбор → фестиваль смены (при изоляции) → дефолт. */
    private function resolveReportFestival(Request $request): string
    {
        $requested = $request->get('festival_id');
        if (is_string($requested) && $requested !== '') {
            return $requested;
        }

        if ((bool) config('baza.festival_isolation')) {
            $changeId = $this->repository->getChangeId((int) \Auth::id());
            if ($changeId !== null) {
                $festivalId = $this->repository->festivalIdForChange($changeId);
                if ($festivalId !== null) {
                    return $festivalId;
                }
            }
        }

        return (string) config('baza.default_festival_id');
    }

    public function viewAddChange(User $user,?int $id = null): View
    {
        if ($id > 0) {
            $findChange = $this->repository->get($id);
            $findChange['user_id'] = Json::decode($findChange['user_id']);
            $findChange['start'] = Carbon::parse($findChange['start'])->format('Y-m-d H:i');
            $findChange['chief_id'] = $this->repository->getChiefId($id);
        }

        return view('change.add', [
            'users' => $user->all(),
            'findChange' => $findChange ?? [],
            'festivals' => $this->festivalList->activeForKpp(),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function save(Request $request): RedirectResponse
    {
        $id = $request->get('id') === null ? null : (int)$request->get('id');
        $chiefId = $request->get('chief') !== null && $request->get('chief') !== ''
            ? (int) $request->get('chief')
            : null;

        // Инвариант Ф2 на уровне формы: у смены обязан быть начальник.
        if ($chiefId === null) {
            return Redirect::back()->withInput()->with('shift_error', 'Выберите начальника смены.');
        }

        try {
            // Фестиваль смены (TD-48): авто-выбор единственного / обязателен при нескольких.
            $festivalId = $this->festivalResolver->resolve($request->get('festival_id'));

            $this->saveChange->save(
                (array) $request->get('compound', []),
                Carbon::parse($request->get('start')),
                $id,
                $chiefId,
                $festivalId,
            );
        } catch (\DomainException $e) {
            // Инвариант «у смены есть начальник», «выберите фестиваль» и пр. — мягко возвращаем на форму.
            return Redirect::back()->withInput()->with('shift_error', $e->getMessage());
        }

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
