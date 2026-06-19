<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance\Strategy;

use Tickets\QrOrder\Application\Issuance\IssuanceStrategyInterface;
use Tickets\QrOrder\Application\Step\CreateLiveTicketsStep;
use Tickets\QrOrder\Application\Step\LinkLiveStep;
use Tickets\QrOrder\Application\Step\SendLiveEmailStep;
use Tickets\QrOrder\Application\Step\SendTelegramStep;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Живой заказ: билеты БЕЗ PDF → письмо о выдаче (без PDF) → связка с live_tickets по номеру
 * (setInBazaLive) → telegram. В el_tickets живой билет не пишется.
 *
 * qr присылает номер живого билета (guests[].number); строка live_tickets с этим номером уже
 * существует — доставка live_tickets (BazaDeliveryDispatcher) проставляет ей el_ticket_id.
 */
final class LiveIssuanceStrategy implements IssuanceStrategyInterface
{
    public function typeOrder(): string
    {
        return TypeOrder::LIVE;
    }

    public function steps(): array
    {
        return [
            CreateLiveTicketsStep::class,
            SendLiveEmailStep::class,
            LinkLiveStep::class,
            SendTelegramStep::class,
        ];
    }
}
