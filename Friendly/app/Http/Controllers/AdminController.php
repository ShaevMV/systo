<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    private $createNewUser;
    private $updateUserProfileInformation;
    private $updateUserPassword;

    public function __construct(
        CreateNewUser                $createNewUser,
        UpdateUserProfileInformation $updateUserProfileInformation,
        UpdateUserPassword           $updateUserPassword
    )
    {
        $this->createNewUser = $createNewUser;
        $this->updateUserProfileInformation = $updateUserProfileInformation;
        $this->updateUserPassword = $updateUserPassword;
    }

    /**
     * @return Application|Factory|View
     */
    public function view()
    {
        return view('admin.index');
    }

    public function users(string $festival_id): View
    {
        $users = User::leftJoin('friendly_tickets', function (JoinClause $join) use ($festival_id){
            $join->on('friendly_tickets.user_id', '=', 'users.id')
                ->where('friendly_tickets.festival_id', '=', $festival_id);
        })->select(['users.*',
                DB::raw('SUM(friendly_tickets.price) AS sum_price'),
                DB::raw('COUNT(friendly_tickets.id) AS count_tickets')
            ])
            ->groupBy('users.id')
            ->get();

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

        return redirect()->route('adminView');
    }

    public function delUser(Request $request): RedirectResponse
    {
        $id = $request->post('id');

        User::destroy($id);

        return redirect()->route('adminUser');
    }

}
