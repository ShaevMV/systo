<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\ListTicket;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Shared\Services\TicketService;
use Shared\Services\CreatingQrCodeService;

class AdminController extends Controller
{
    private $createNewUser;
    private $updateUserProfileInformation;
    private $updateUserPassword;
    private CreatingQrCodeService $creatingQrCodeService;
    private TicketService $ticketService;

    public function __construct(
        CreateNewUser                $createNewUser,
        UpdateUserProfileInformation $updateUserProfileInformation,
        UpdateUserPassword           $updateUserPassword,
        CreatingQrCodeService        $creatingQrCodeService,
        TicketService $ticketService
    )
    {
        $this->createNewUser = $createNewUser;
        $this->updateUserProfileInformation = $updateUserProfileInformation;
        $this->updateUserPassword = $updateUserPassword;
        $this->creatingQrCodeService = $creatingQrCodeService;
        $this->ticketService = $ticketService;
    }

    /**
     * @return Application|Factory|View
     */
    public function view()
    {
        return view('admin.index');
    }

    public function users()
    {
        $users = User
            ::get();

        return view('admin.users', [
            'users' => $users,
        ]);
    }

    public function editUser(int $id)
    {
        return view('auth.register', [
            'user' => User::find($id),
        ]);
    }

    public function createUser()
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function registerUser(Request $request): RedirectResponse
    {
        if ($id = $request->post('id', null)) {
            $user = User::find($id);
            $this->updateUserProfileInformation->update($user, $request->post());
            if (null !== $request->post('password', null)) {
                $this->updateUserPassword->update($user, $request->post());
            }
        } else {
            $this->createNewUser->create($request->post());
        }

        return redirect()->route('adminUser');
    }

    public function delUser(Request $request): RedirectResponse
    {
        $id = $request->post('id');

        User::destroy($id);

        return redirect()->route('adminUser');
    }

    public function tickets()
    {
        $tickets = ListTicket::where(
            'id', '>=', 1000
        )->get();

        return view('admin.tickets', [
            'tickets' => $tickets,
        ]);
    }

    public function delTicket(Request $request): RedirectResponse
    {
        $id = $request->post('id');

        ListTicket::destroy($id);
        $this->ticketService->deleteTicketList($id);

        return redirect()->route('adminTickets');
    }

    public function getPdf(int $id): Response
    {
        /** @var ListTicket $ticket */
        $ticket = ListTicket::whereId($id)->first();
        $pdf = $this->creatingQrCodeService->createPdf('S' . $id, $ticket->fio, $ticket->email, $ticket->project);

        return $pdf->download('Билет для ' . $ticket->fio . '.pdf');
    }
}
