<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * Tap для канала `structured`: навешивает маскировку ПДн (MaskPiiProcessor) на Monolog-логгер.
 * Подключается в config/logging.php ('tap' => [MaskPiiTap::class]).
 */
final class MaskPiiTap
{
    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->pushProcessor(new MaskPiiProcessor());
    }
}
