<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSendListTicketEmail;
use App\Models\ListTicket;
use Shared\Services\TicketService;
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
        return view('tickets/form', [
            'user' => Auth::user(),
        ]);
    }

    public function add(Request $request)
    {
        DB::beginTransaction();
        try {
            $ids = [];
            $nameList = explode("\r\n", $request->get("list"));
            if (count($nameList) === 0) {
                throw new \Exception('Не указан состав');
            }
            foreach ($nameList as $value) {
                $model = new ListTicket();
                $model->fio = $value;
                $model->project = $request->post('project');
                $model->curator = $request->post('curator');
                $model->email = $request->post('email');
                $model->comment = $request->post('comment') ?? '';
                $model->festival_id = env('UUID_SECOND_FESTIVAL', '9d679bcf-b438-4ddb-ac04-023fa9bff4b3');
                $model->user_id = Auth::id();
                $model->saveOrFail();

                $ids['S' . $model->id] = $value;
                $this->ticketService->pushTicketList($model);
            }

            Bus::chain([
                new ProcessSendListTicketEmail(
                    $request->post('email'),
                    $ids,
                    $request->post('project')
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
