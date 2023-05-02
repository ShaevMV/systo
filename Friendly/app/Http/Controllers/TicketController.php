<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSendTicketEmail;
use App\Models\FriendlyTicket;
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


    public function tickets()
    {
        $tickets = FriendlyTicket::where(
            'id', '>=', 1000
        )->get();

        return view('admin.tickets', [
            'tickets' => $tickets,
        ]);
    }

    public function delTicket(Request $request): RedirectResponse
    {
        $id = $request->post('id');

        FriendlyTicket::destroy($id);
        $this->ticketService->deleteTicketFriendly($id);
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
