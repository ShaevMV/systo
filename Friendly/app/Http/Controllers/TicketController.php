<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSendLiveTicketEmail;
use App\Jobs\ProcessSendTicketEmail;
use App\Models\FriendlyTicket;
use App\Models\LiveTicket;
use Illuminate\Contracts\View\View;
use Shared\Services\CreatingQrCodeService;
use Shared\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Bus;

class TicketController extends Controller
{
    private TicketService $ticketService;
    private CreatingQrCodeService $creatingQrCodeService;

    public function __construct(
        TicketService         $ticketService,
        CreatingQrCodeService $creatingQrCodeService
    )
    {
        $this->middleware('auth');
        $this->ticketService = $ticketService;
        $this->creatingQrCodeService = $creatingQrCodeService;
    }

    public function view()
    {
        return view('tickets/form', [
            'user' => Auth::user(),
        ]);
    }

    public function viewLive()
    {
        return view('live/form', [
            'user' => Auth::user(),
        ]);
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
                $model->festival_id = env('UUID_SECOND_FESTIVAL','9d679bcf-b438-4ddb-ac04-023fa9bff4b4');
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

            $massage = 'Ура! Всё получилось!
Билеты отправлены на указанную почту!';
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            $massage = $e->getMessage();
        }

        return redirect('/')
            ->with('status', $massage);
    }

    public function addLiveTicket(Request $request)
    {
        $price = $request->post('price') / $request->post('count');
        DB::beginTransaction();
        try {
            foreach ($request->post('kilter') as $value) {
                $model = new LiveTicket();
                $model->fio_friendly = $request->post('fio');;
                $model->fio = $request->post('fio_seller');
                $model->seller = $request->post('seller');
                $model->email = $request->post('email');
                $model->comment = $request->post('comment') ?? '';
                $model->price = $price;
                $model->festival_id = env('UUID_SECOND_FESTIVAL','9d679bcf-b438-4ddb-ac04-023fa9bff4b4');
                $model->phone = $request->post('phone') ?? '';
                $model->user_id = Auth::id();
                $model->kilter = $value;
                $model->saveOrFail();

            }

            Bus::chain([
                new ProcessSendLiveTicketEmail(
                    $request->post('email')
                ),
            ])->dispatch();

            $massage = 'Ура! Всё получилось! Живые билеты зарегистрированы';
            DB::commit();
        } catch (Throwable $e) {
            DB::rollback();
            $massage = $e->getMessage();
        }

        return redirect('/live')
            ->with('status', $massage);
    }


    public function tickets(Request $request): View
    {
        $festival_id = $request->get('festival_id');
        if($request->get('type') === 'friendly_tickets') {
            $tickets = FriendlyTicket::where(
                'festival_id', '=', $festival_id
            )->get();
        } else {
            $tickets = LiveTicket::where(
                'festival_id', '=', $festival_id
            )->get();
        }

        return view('admin.tickets', [
            'tickets' => $tickets,
            'type' => $request->get('type'),
        ]);
    }

    public function delTicket(Request $request): RedirectResponse
    {
        $id = $request->post('id');
        if ($request->post('type') === 'friendly_tickets') {
            FriendlyTicket::destroy($id);
            $this->ticketService->deleteTicketFriendly($id);
        } else {
            LiveTicket::destroy($id);
        }

        return redirect()->route('adminTickets');
    }

    public function getPdf(int $id): Response
    {
        /** @var FriendlyTicket $ticket */
        $ticket = FriendlyTicket::whereId($id)->first();
        $pdf = $this->creatingQrCodeService->createPdf('f' . $id, $ticket->fio_friendly, $ticket->email, 'f');

        return $pdf->download('Билет для ' . $ticket->fio_friendly . '.pdf');
    }
}
