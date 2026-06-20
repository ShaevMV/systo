<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\AddTicketsInReport\AddTicketsInReport;
use Baza\Changes\Applications\GetCurrentChanges\GetCurrentChanges;
use Baza\EntryOutbox\Applications\EntryOutboxApplication;
use Baza\Tickets\Applications\Enter\EnterTicket;
use Baza\Tickets\Applications\Scan\SearchEngine;
use Baza\Tickets\Repositories\BlacklistRepositoryInterface;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class ScanController extends Controller
{
    public function __construct(
        private SearchEngine $searchEngine,
        private EnterTicket $enterTicket,
        private GetCurrentChanges $getCurrentChanges,
        private AddTicketsInReport $addTicketsInReport,
        private EntryOutboxApplication $entryOutbox,
        private BlacklistRepositoryInterface $blacklist,
    ) {}

    public function search(Request $request): JsonResponse
    {
        try {
            $link = $request->get('search');
            if (is_null($link)) {
                throw new DomainException('Не опознанный билет просканируй снова!');
            }

            return response()->json(
                $this->searchEngine->get($link)->toArray()
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            return response()->json($exception->getMessage(), 422);
        }
    }

    /**
     * Впуск гостя на КПП. Порядок шагов важен:
     *   1) по сотруднику (Auth::id() — из сессии, НЕ из тела запроса) находим id его
     *      ТЕКУЩЕЙ открытой смены (getCurrentChanges);
     *   2) помечаем билет впущенным (skip): change_id = id смены, date_change = now.
     *      skip() бросает исключение, если билет НЕ найден или УЖЕ был пропущен —
     *      серверная защита от повторного впуска (не только фронт);
     *   3) только при успешном skip — +1 к счётчику прошедших билетов в отчёте смены.
     *
     * Маршрут защищён middleware('auth') в routes/web.php (web-группа: сессия + CSRF).
     */
    public function enter(Request $request): JsonResponse
    {
        try {
            // B6: отозванный билет не пускаем даже онлайн (defense-in-depth к клиентскому blacklist).
            if ($this->blacklist->isRevoked(null, (int) $request->get('id'))) {
                throw new DomainException('Билет отозван');
            }

            $changeId = $this->getCurrentChanges->getId((int) \Auth::id());

            $this->enterTicket->skip(
                $request->get('type'),
                (int) $request->get('id'),
                $changeId,
            );

            $this->addTicketsInReport->increment($changeId, $request->get('type'));

            // Ф4: фиксируем факт входа в outbox для вебхука Baza→org (best-effort, впуск не падает).
            $this->entryOutbox->record($request->get('type'), (int) $request->get('id'), $changeId);

            return response()->json('OK');
        } catch (Throwable $e) {
            return response()->json($e->getMessage(), 422);
        }
    }
}
