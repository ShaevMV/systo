<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class Bot
{
    private const TOKEN = 'CiRP3hdM6r5MBqzQCvquvSlJ9CJ1';

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = $request->headers->get('auth-token');

        if ($token === self::TOKEN) {
            return $next($request);
        }

        return redirect('/profile');
    }
}
