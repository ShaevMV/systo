<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Shared\Infrastructure\Logging\LogSanitizer;

/**
 * Monolog-процессор маскировки ПДн (152-ФЗ). Навешивается на канал `structured`
 * (через MaskPiiTap), чтобы даже прямые Log::* с ПДн не утекли в централизованный лог.
 * Прогоняет message/context/extra через LogSanitizer ДО форматирования.
 */
final class MaskPiiProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: LogSanitizer::maskText($record->message),
            context: LogSanitizer::sanitizeArray($record->context),
            extra: LogSanitizer::sanitizeArray($record->extra),
        );
    }
}
