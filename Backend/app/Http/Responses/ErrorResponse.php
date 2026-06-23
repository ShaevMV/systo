<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Shared\Domain\DomainError;
use Throwable;

/**
 * Единый ответ об ошибке для контроллеров: {success:false, code, message} — БЕЗ file/line
 * и сырого Exception::getMessage() с возможными ПДн (TD-29, 152-ФЗ).
 *
 * Полную диагностику (класс/сообщение/файл) пишет в канал `structured` (сервер-сайд,
 * с маскировкой ПДн через MaskPiiTap) — чтобы ошибка была видна в централизованном логе,
 * но не утекала в HTTP-ответ клиенту.
 */
final class ErrorResponse
{
    /**
     * @param int $status HTTP-статус ответа. По умолчанию 200 — сохраняет исторический контракт
     *                    org-контроллеров (фронт смотрит на поле `success`, а не на код ответа).
     */
    public static function fromThrowable(Throwable $e, int $status = 200): JsonResponse
    {
        $isDomain = $e instanceof DomainError;
        $code = $isDomain ? $e->errorCode() : ErrorCode::INTERNAL;
        // Сообщение пользователю: доменное (errorMessage безопасен по смыслу) либо нейтральное.
        $message = $isDomain ? $e->getMessage() : ErrorCode::INTERNAL_MESSAGE;

        // Полная диагностика — только в structured-лог (ПДн маскируются tap'ом канала).
        Log::channel('structured')->error('controller_exception', [
            'error_code' => $code,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
