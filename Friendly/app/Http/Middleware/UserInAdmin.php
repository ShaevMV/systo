<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserInAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if(!Auth::user()) {
            return redirect('/login');
        }

        if (Auth::user()->is_admin === 1) {
            return $next($request);
        }


        return redirect('/profile');
    }
}
