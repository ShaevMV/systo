<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSendTicketEmail;
use App\Models\FriendlyTicket;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Bus;

class TicketController extends Controller
{
    private TicketService $ticketService;

    public function __construct(
        TicketService $ticketService
    )
    {
        $this->middleware('auth');
        $this->ticketService = $ticketService;
    }

    public function view()
    {
        return view('tickets/form',[
            'user'=> Auth::user(),
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
                $this->ticketService->pushTicket($model);
                $ids['f' . $model->id] =  $value;
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
}
