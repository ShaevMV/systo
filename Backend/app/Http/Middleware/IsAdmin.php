<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tickets\User\Account\Helpers\AccountRoleHelper;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response|RedirectResponse|JsonResponse
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        if (Auth::user() &&
            (((bool)Auth::user()->is_admin) === true || Auth::user()->role === AccountRoleHelper::admin)
        ) {
            return $next($request);
        }


        return response()->json([
            'errors' => ['error' => 'Forbidden']
        ], 403);
    }
}
