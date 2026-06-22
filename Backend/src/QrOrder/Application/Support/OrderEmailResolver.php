<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Support;

use App\Mail\OrderToPaid;
use App\Mail\OrderToPaidFriendly;
use Illuminate\Mail\Mailable;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

/**
 * Резолвер письма с билетами по типу заказа (C3, уровень order-type).
 *
 * Mailable выбирается по type_order: friendly → OrderToPaidFriendly, иначе → OrderToPaid.
 * Blade-шаблон ВНУТРИ письма (парковка orderToPaidAvto, детский ...Child) определяется по типу
 * билета из ticket_type_festival (TicketResponse.emailView) — это ДРУГОЙ уровень (ticket-type),
 * поэтому парковка-override корректно работает поверх любого type_order, не конфликтуя.
 *
 * list/live имеют принципиально иные письма и собственные шаги — здесь не обрабатываются.
 */
final class OrderEmailResolver
{
    /**
     * @param  array<int, TicketResponse>  $responses
     * @param  int|null  $orderNo  номер заказа qr (external_order_no) для подстановки {{ kilter }}
     */
    public function resolve(?string $typeOrder, array $responses, ?Uuid $ticketTypeId, ?string $comment, ?int $orderNo = null): Mailable
    {
        $ticketTypeId ??= Uuid::random();

        return match (TypeOrder::normalize($typeOrder)) {
            TypeOrder::FRIENDLY => new OrderToPaidFriendly($responses, $ticketTypeId, $comment, null, $orderNo),
            default => new OrderToPaid($responses, $ticketTypeId, $comment, null, $orderNo),
        };
    }
}
