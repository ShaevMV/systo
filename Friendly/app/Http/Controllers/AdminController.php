<?php

namespace App\Http\Controllers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Auto;
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
        $usersFriendly = User::leftJoin('friendly_tickets', function (JoinClause $join) use ($festival_id){
            $join->on('friendly_tickets.user_id', '=', 'users.id')
                ->where('friendly_tickets.festival_id', '=', $festival_id);
        })->select(['users.*',
                DB::raw('SUM(friendly_tickets.price) AS sum_price_friendly'),
                DB::raw('COUNT(friendly_tickets.id) AS count_tickets_friendly'),
            ])
            ->groupBy('users.id')
            ->get()
            ->toArray();

        $usersLive = User::leftJoin('live_tickets', function (JoinClause $join) use ($festival_id){
            $join->on('live_tickets.user_id', '=', 'users.id')
                ->where('live_tickets.festival_id', '=', $festival_id);
        })->select(['users.*',
            DB::raw('SUM(live_tickets.price) AS sum_price_live'),
            DB::raw('COUNT(live_tickets.id) AS count_tickets_live'),
        ])
            ->groupBy('users.id')
            ->get()
            ->toArray();

        $usersList = User::leftJoin('list_tickets', function (JoinClause $join) use ($festival_id){
            $join->on('list_tickets.user_id', '=', 'users.id')
                ->where('list_tickets.festival_id', '=', $festival_id);
        })->select(['users.*',
            DB::raw('COUNT(list_tickets.id) AS count_tickets_list'),
        ])
            ->groupBy('users.id')
            ->get()
            ->toArray();

        $users = [];
        foreach ($usersFriendly as $value) {
            $users[$value['id']] = $value;
        }

        foreach ($usersList as $value) {
            $users[$value['id']] = array_merge($users[$value['id']], $value);
        }

        foreach ($usersLive as $value) {
            $users[$value['id']] = array_merge($users[$value['id']], $value);
        }

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

        return redirect()->route('adminUser',[env('UUID_FESTIVAL', '9d679bcf-b438-4ddb-ac04-023fa9bff4b4')]);
    }

    public function getAuto(): View|Factory|Application
    {
        return view('admin.auto',[
            'tickets' => Auto::all()
        ]);
    }

    public function delAuto(Request $request)
    {
        $id = $request->post('id');

        Auto::destroy($id);

        return redirect()->route('getAuto');
    }
}
