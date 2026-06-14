<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance\Strategy;

use Tickets\QrOrder\Application\Issuance\IssuanceStrategyInterface;
use Tickets\QrOrder\Application\Step\CreateTicketsStep;
use Tickets\QrOrder\Application\Step\PushToBazaStep;
use Tickets\QrOrder\Application\Step\SendOrderEmailStep;
use Tickets\QrOrder\Application\Step\SendTelegramStep;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Friendly-заказ: набор шагов как у обычного (билеты + el_tickets + telegram), но письмо —
 * friendly-вариант. Сам выбор Mailable (OrderToPaidFriendly) делает OrderEmailResolver по
 * type_order внутри SendOrderEmailStep, поэтому список шагов совпадает с Regular (DRY).
 */
final class FriendlyIssuanceStrategy implements IssuanceStrategyInterface
{
    public function typeOrder(): string
    {
        return TypeOrder::FRIENDLY;
    }

    public function steps(): array
    {
        return [
            CreateTicketsStep::class,
            SendOrderEmailStep::class,
            PushToBazaStep::class,
            SendTelegramStep::class,
        ];
    }
}
