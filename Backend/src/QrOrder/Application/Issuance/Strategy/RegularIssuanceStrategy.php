<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance\Strategy;

use Tickets\QrOrder\Application\Issuance\IssuanceStrategyInterface;
use Tickets\QrOrder\Application\Step\CreateTicketsStep;
use Tickets\QrOrder\Application\Step\SendOrderEmailStep;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Обычный заказ: создать билеты + PDF/QR → одно письмо со всеми PDF.
 *
 * Фаза 2 добавит сюда PushToBazaStep (запись в Baza), фаза 3 — SendTelegramStep.
 */
final class RegularIssuanceStrategy implements IssuanceStrategyInterface
{
    public function typeOrder(): string
    {
        return TypeOrder::REGULAR;
    }

    public function steps(): array
    {
        return [
            CreateTicketsStep::class,
            SendOrderEmailStep::class,
        ];
    }
}
