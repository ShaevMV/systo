<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * Возвращаем базовый Symfony Response — он покрывает все варианты
     * (HTTP Response, RedirectResponse, JsonResponse, BinaryFileResponse и т.п.).
     * Базовый Laravel Response слишком узкий — ломал отдачу файлов (download).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::user() &&  (bool)Auth::user()->is_admin === true) {
            return $next($request);
        }


        return response()->json([
            'errors' => ['error' => 'Forbidden']
        ], 403);
    }
}
