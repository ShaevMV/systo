<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance\Strategy;

use Tickets\QrOrder\Application\Issuance\IssuanceStrategyInterface;
use Tickets\QrOrder\Application\Step\CreateTicketsStep;
use Tickets\QrOrder\Application\Step\PushToBazaStep;
use Tickets\QrOrder\Application\Step\SendOrderEmailStep;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Обычный заказ: создать билеты + PDF/QR → одно письмо со всеми PDF → запись в Baza (el_tickets).
 *
 * Фаза 3 добавит сюда SendTelegramStep.
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
            PushToBazaStep::class,
        ];
    }
}
