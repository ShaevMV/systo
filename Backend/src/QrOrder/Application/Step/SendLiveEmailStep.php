<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use App\Mail\OrderToLiveTicketIssued;
use Illuminate\Support\Facades\Mail;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Письмо для живого заказа: уведомление о выдаче живого билета (OrderToLiveTicketIssued), БЕЗ PDF.
 * Одно письмо на заказ по типу билета первого гостя.
 */
final class SendLiveEmailStep implements PipelineStepInterface
{
    public function name(): string
    {
        return 'send_live_email';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        $responses = $carry['responses'] ?? [];
        $log = PipelineLog::logger();

        if ($responses === []) {
            $log->warning('send_live_email.skipped_no_tickets', ['order_id' => $order->getId()->value()]);

            return $carry;
        }

        $ticketTypeId = $carry['firstTicketTypeId'] ?? Uuid::random();

        Mail::to($order->getEmail())->send(new OrderToLiveTicketIssued($ticketTypeId));

        $log->info('send_live_email.sent', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
        ]);

        return $carry;
    }
}
