<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSendListTicketEmail;
use App\Jobs\ProcessSendLiveTicketEmail;
use App\Jobs\ProcessSendTicketEmail;
use App\Models\Auto;
use App\Models\FriendlyTicket;
use App\Models\ListTicket;
use App\Models\LiveTicket;
use App\Models\User;
use App\Services\CsvFileService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Redirect;
use Shared\Services\CreatingQrCodeService;
use Shared\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Translation\Loader\CsvFileLoader;
use Throwable;
use Illuminate\Support\Facades\Bus;

class TicketController extends Controller
{
    private TicketService $ticketService;
    private CreatingQrCodeService $creatingQrCodeService;
    private string $festivalId;

    public function __construct(
        TicketService          $ticketService,
        CreatingQrCodeService  $creatingQrCodeService,
        private CsvFileService $csvFileService,
    )
    {
        $this->middleware('auth');
        $this->ticketService = $ticketService;
        $this->creatingQrCodeService = $creatingQrCodeService;
        $this->festivalId = '9d679bcf-b438-4ddb-ac04-023fa9bff4b5';
    }

    public function view(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->is_list && !$user->is_admin) {
            return Redirect::route('viewListTickets');
        }

        return view('tickets/form', [
            'user' => $user,
            'success' => $this->getHumanSuccessForEl($request->get('success', null))
        ]);
    }

    private function getHumanSuccessForEl(?string $success): ?string
    {
        if (null === $success) {
            return null;
        }

        return $success ? 'Ура! Всё получилось!
Билеты отправлены на указанную почту! И придёт в течении часа!!! ' : 'К сожалению что то пошло не так(!';
    }


    public function viewLive(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user->is_list && !$user->is_admin) {
            return Redirect::route('viewListTickets');
        }

        return view('live/form', [
            'user' => $user,
            'success' => $this->getHumanSuccessForLive(
                $request->get('success', null),
                $request->get('value', null)
            )
        ]);
    }


    public function viewList(Request $request): Factory|View|Application|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (!$user->is_list && !$user->is_admin) {
            return Redirect::route('view');
        }

        return view('list/form', [
            'user' => $user,
            'success' => $this->getHumanSuccessForLive(
                $request->get('success', null)
            )
        ]);
    }

    public function addListTicket(Request $request)
    {
        DB::beginTransaction();
        $massage = '';

        try {

            $nameAuto = explode("\r\n", $request->post("auto"));
            Log::info(implode(',', $nameAuto));
            foreach ($nameAuto as $valueOld) {
                $value = trim($valueOld);
                if (empty(trim($value))) {
                    continue;
                }
                $model = new Auto();
                $model->auto = $value;
                $model->project = $request->post('project');
                $model->curator = $request->post('curator');
                $model->comment = $request->post('comment') ?? '';
                $model->festival_id = $this->festivalId;
                $model->user_id = Auth::id();
                $model->saveOrFail();
                $this->ticketService->pushAutoList($model);
            }

            $ids = [];
            $nameList = explode("\r\n", $request->post("list"));
            Log::info(implode(',', $nameList));
            /*if (count($nameList) === 0) {
                throw new \Exception('Не указан состав');
            }*/

            foreach ($nameList as $valueOld) {
                $value = trim($valueOld);
                if (empty(trim($value))) {
                    continue;
                }
                $model = new ListTicket();
                $model->fio = $value;
                $model->project = $request->post('project');
                $model->curator = $request->post('curator');
                $model->email = $request->post('email');
                $model->comment = $request->post('comment') ?? '';
                $model->festival_id = $this->festivalId;
                $model->user_id = Auth::id();
                $model->phone = $request->post('phone') ?? '';
                $model->saveOrFail();

                $ids['S' . $model->id] = $value;
                $this->ticketService->pushTicketList($model);
            }
            if (count($ids) > 0) {
                Bus::chain([
                    new ProcessSendListTicketEmail(
                        $request->post('email'),
                        $ids,
                        $request->post('project')
                    ),
                ])->dispatch();


            }
            $success = 1;
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            $success = 0;
        }

        return Redirect::route('viewListTickets', [
            'success' => $success,
        ]);
    }

    private function getHumanSuccessForLive(?string $success, ?string $value = null): ?string
    {
        if (null === $success) {
            return null;
        }

        return match ($success) {
            '0' => 'К сожалению что то пошло не так(!',
            '-1' => "Билет с номером $value выходит из допустимого диапазона!",
            '-2' => "Билет с номером $value уже зарегистрирован, проверьте правильность номеров и попробуйте снова! Или свяжитесь с администратором!",
            '1' => 'Ура! Всё получилось! Билеты зарегистрированы',
            default => null,
        };
    }

    public function add(Request $request)
    {
        $price = $request->post('price') / $request->post('count');
        DB::beginTransaction();
        try {
            $ids = [];
            foreach ($request->post('fio') as $value) {
                $model = new FriendlyTicket();
                $model->fio_friendly = $value;
                $model->fio = $request->post('fio_seller');
                $model->seller = $request->post('seller');
                $model->email = $request->post('email');
                $model->comment = $request->post('comment') ?? '';
                $model->price = $price;
                $model->festival_id = $this->festivalId;
                $model->phone = $request->post('phone') ?? '';
                $model->user_id = Auth::id();
                $model->saveOrFail();
                $this->ticketService->pushTicketFriendly($model);
                $ids['f' . $model->id] = $value;
            }

            Bus::chain([
                new ProcessSendTicketEmail(
                    $request->post('email'),
                    $ids
                ),
            ])->dispatch();

            $success = true;
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            $success = false;
        }

        return Redirect::route('viewAddTickets', ['success' => $success]);
    }

    public function addLiveTicket(Request $request)
    {
        $price = $request->post('price') / $request->post('count');
        $success = 1;
        $errorValue = null;
        DB::beginTransaction();
        try {
            foreach ($request->post('kilter') as $value) {
                $model = new LiveTicket();

                if ((int)$value < 1 || (int)$value > 5500) {
                    $success = -1;
                }

                if (LiveTicket::where([
                    'kilter' => (int)$value,
                    'festival_id' => $this->festivalId
                ])->exists()) {
                    $success = -2;
                    $errorValue = $value;
                }
                $model->fio_friendly = $request->post('fio');;
                $model->fio = $request->post('fio_seller');
                $model->seller = $request->post('seller');
                $model->email = $request->post('email');
                $model->comment = $request->post('comment') ?? '';
                $model->price = $price;
                $model->festival_id = $this->festivalId;
                $model->phone = $request->post('phone') ?? '';
                $model->user_id = Auth::id();
                $model->kilter = (int)$value;
                $model->saveOrFail();
            }

            Bus::chain([
                new ProcessSendLiveTicketEmail(
                    $request->post('email')
                ),
            ])->dispatch();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            $success = 0;
        }

        return Redirect::route('viewLiveTickets', [
            'success' => $success,
            'value' => $errorValue,
        ]);
    }


    public function tickets(Request $request): View
    {
        $festival_id = $request->get('festival_id');
        $tickets = match ($request->get('type')) {
            'friendly_tickets' => FriendlyTicket::where(
                'festival_id', '=', $festival_id
            )->get(),
            'live_tickets' => LiveTicket::where(
                'festival_id', '=', $festival_id
            )->get(),
            'list_tickets' => ListTicket::where(
                'festival_id', '=', $festival_id
            )->get(),
        };

        return view('admin.tickets', [
            'tickets' => $tickets,
            'type' => $request->get('type'),
        ]);
    }

    public function profile(Request $request): View
    {
        $el = FriendlyTicket::where([
            'festival_id' => $this->festivalId,
            'user_id' => Auth::id()
        ])->get();

        $live = LiveTicket::where([
            'festival_id' => $this->festivalId,
            'user_id' => Auth::id()
        ])->get();

        $list = ListTicket::where([
            'festival_id' => $this->festivalId,
            'user_id' => Auth::id()
        ])->get();

        $auto = Auto::where([
            'festival_id' => $this->festivalId,
            'user_id' => Auth::id()
        ])->get();

        return view('tickets.profile', [
            'ticketsEl' => $el,
            'ticketsLive' => $live,
            'ticketsList' => $list,
            'auto' => $auto,
        ]);
    }


    public function delTicket(Request $request): RedirectResponse
    {
        $id = $request->post('id');
        switch ($request->post('type')) {
            case 'friendly_tickets':
                FriendlyTicket::destroy($id);
                $this->ticketService->deleteTicketFriendly($id);
                break;
            case 'live_tickets':
                LiveTicket::destroy($id);
                break;
            case 'list_tickets':
                ListTicket::destroy($id);
                $this->ticketService->deleteTicketList($id);
                break;
            case 'auto':
                Auto::destroy($id);
                $this->ticketService->deleteAuto($id);
                break;
        }

        if ($request->post('url', null)) {
            return redirect($request->post('url'));
        }

        return redirect()->route('adminTickets', [
            'festival_id' => $this->festivalId,
            'type' => $request->get('type'),
        ]);
    }

    public function getPdf(string $id): Response
    {
        $idInt = preg_replace("/[^,.0-9]/", '', $id);
        if (strripos($id, 'f') !== false) {
            /** @var FriendlyTicket $ticket */
            $ticket = FriendlyTicket::whereId($idInt)->first();
            $pdf = $this->creatingQrCodeService->createPdf($id, $ticket->fio_friendly, $ticket->email, '');
            $fio = $ticket->fio_friendly;
        } else {
            /** @var ListTicket $ticket */
            $ticket = ListTicket::whereId($idInt)->first();
            $pdf = $this->creatingQrCodeService->createPdf($id, $ticket->fio, $ticket->email, '');
            $fio = $ticket->fio;
        }


        return $pdf->download('Билет для ' . $fio . '.pdf');
    }


    /**
     * @throws Throwable
     */
    public function addListTicketInFile(Request $request)
    {
        $file = $request->file('listFile');
        $success = (int)$this->csvFileService->insertListInFile($file, $this->festivalId, \Auth::id());

        return Redirect::route('viewListTickets', [
            'success' => $success,
        ]);
    }
}
