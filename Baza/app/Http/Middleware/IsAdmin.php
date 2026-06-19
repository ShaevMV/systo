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
     * Роль администратора в Baza = единственный флаг users.is_admin === true.
     * Других ролей нет (плоская бинарная модель прав). Закрывает админ-роуты:
     * /report, /change/*, /sync/*.
     *
     * Возвращаем базовый Symfony Response — он покрывает все варианты
     * (HTTP Response, RedirectResponse, JsonResponse, BinaryFileResponse и т.п.).
     * Базовый Laravel Response слишком узкий — ломал отдачу файлов (download).
     *
     * ВНИМАНИЕ: при отказе отдаём JSON 403 даже для web-страниц (report/change/sync) —
     * для UI-роута это выглядит как голый JSON, а не страница ошибки.
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
