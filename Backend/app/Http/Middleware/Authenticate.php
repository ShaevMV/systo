<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Backend — API-only: страницы логина нет, поэтому НЕ редиректим
     * (иначе route('login') кидал бы RouteNotFoundException → 500). Возвращаем null;
     * 401 JSON отдаёт Handler::unauthenticated (см. TD-33).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        return null;
    }
}
