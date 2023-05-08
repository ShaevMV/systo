<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Baza\Changes\Applications\OpenAndClose\OpenAndCloseChanges;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function __construct(
    )
    {
        $this->middleware('guest')->except('logout');
    }

    public function loginPage(): View
    {
        return view('auth.login', [
            'pageSlug' => null
        ]);
    }

    public function registerPage(): View
    {
        return view('auth.register');
    }

    /**
     * @throws \Throwable
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended();
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * @throws \Throwable
     */
    public function logout(): RedirectResponse
    {
        Session::flush();

        Auth::logout();

        return redirect()->intended('login');
    }

    public function passwordRequestPage(): View
    {
        return view('auth.passwords.reset');
    }
}
