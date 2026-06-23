<?php

declare(strict_types=1);

namespace App\Http\Responses;

/**
 * Каталог обобщённых кодов ошибок API. Доменные коды дают сами исключения
 * (Shared\Domain\DomainError::errorCode()); здесь — родовые коды для не-доменных ошибок.
 */
final class ErrorCode
{
    public const INTERNAL = 'internal_error';
    public const VALIDATION = 'validation_error';
    public const NOT_FOUND = 'not_found';
    public const FORBIDDEN = 'forbidden';
    public const UNAUTHORIZED = 'unauthorized';

    /** Нейтральное сообщение пользователю для не-доменных ошибок (без раскрытия деталей). */
    public const INTERNAL_MESSAGE = 'Произошла ошибка при обработке запроса. Мы уже разбираемся.';
}
