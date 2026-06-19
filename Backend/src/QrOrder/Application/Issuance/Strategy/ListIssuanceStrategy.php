<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance\Strategy;

use Tickets\QrOrder\Application\Issuance\IssuanceStrategyInterface;
use Tickets\QrOrder\Application\Step\CreateTicketsStep;
use Tickets\QrOrder\Application\Step\PushToBazaStep;
use Tickets\QrOrder\Application\Step\SendListEmailStep;
use Tickets\QrOrder\Application\Step\SendTelegramStep;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Заказ-список: билеты + PDF → письмо OrderListApproved (по локации) → запись в spisok_tickets
 * (BazaDeliveryDispatcher маршрутизирует по isList) → telegram.
 *
 * CreateTicketsStep заполняет curator/location/project из контракта → TicketResponse::isList()=true.
 * Цены у списка нет (price игнорируется).
 */
final class ListIssuanceStrategy implements IssuanceStrategyInterface
{
    public function typeOrder(): string
    {
        return TypeOrder::LIST;
    }

    public function steps(): array
    {
        return [
            CreateTicketsStep::class,
            SendListEmailStep::class,
            PushToBazaStep::class,
            SendTelegramStep::class,
        ];
    }
}
