<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users
     *
     * @param User $model
     * @return View
     */
    public function index(User $model): View
    {
        return view('users.index', ['users' => $model->paginate(15)]);
    }
}
