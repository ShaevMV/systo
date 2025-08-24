<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class Bot
{
    public const TOKEN_BOT = 'CiRP3hdM6r5MBqzQCvquvSlJ9CJ1';
    public const TOKEN_AI = 'PCf4yeeM8prVGee3zbArQGQP2eGpPHsV';

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

        if (in_array($token, [
                self::TOKEN_BOT,
                self::TOKEN_AI,
            ])) {
            return $next($request);
        }

        return redirect('/');
    }

    public static function getUserEmailByToken(string $token ): string
    {
        return match($token) {
            self::TOKEN_BOT => 'bot@telegram.com',
            self::TOKEN_AI => 'bot@ai.com',
        };
    }
}
