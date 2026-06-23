<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Shared\Domain\DomainError;
use Throwable;
use Sentry\Laravel\Integration;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });

        // Структурная PII-безопасная запись необработанных исключений в канал `structured`
        // (готовность к Graylog/Loki): error_code + класс + HTTP-статус + маршрут, БЕЗ сырого
        // getMessage() с ПДн. Канал маскирует ПДн (MaskPiiTap) и ignore_exceptions=true.
        $this->reportable(function (Throwable $e) {
            Log::channel('structured')->error('unhandled_exception', [
                'error_code' => $e instanceof DomainError ? $e->errorCode() : 'internal_error',
                'exception' => $e::class,
                'status' => $this->statusFor($e),
                'path' => request()->path(),
                'route' => optional(request()->route())->getName(),
            ]);
        });
    }

    /** HTTP-статус исключения (для structured-лога); не-HTTP исключения → 500. */
    private function statusFor(Throwable $e): int
    {
        return method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500;
    }

    /**
     * Неаутентифицированный запрос → всегда чистый 401 JSON (TD-33).
     *
     * Backend — API-only (login-страницы нет). Дефолтный обработчик для
     * не-JSON запросов пытался редиректить на route('login'), которого нет →
     * RouteNotFoundException → 500. Теперь любой защищённый роут без/с протухшим
     * токеном отдаёт 401, а не 500 (важно для корректной обработки на фронте).
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse
    {
        return response()->json(
            ['message' => $exception->getMessage() ?: 'Unauthenticated.'],
            401,
        );
    }
}
