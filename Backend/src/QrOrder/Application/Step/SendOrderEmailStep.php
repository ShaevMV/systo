<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use App\Mail\OrderToPaid;
use Illuminate\Support\Facades\Mail;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг 2: одно письмо получателю (order.email) со всеми PDF-билетами заказа.
 *
 * PDF рендерится внутри письма (OrderToPaid) из TicketResponse — поэтому шаг НЕ зависит от
 * завершения ProcessCreatingQRCode. Берёт responses из carry (шаг create_tickets).
 */
final class SendOrderEmailStep implements PipelineStepInterface
{
    public function name(): string
    {
        return 'send_order_email';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        $responses = $carry['responses'] ?? [];
        $log = PipelineLog::logger();

        if ($responses === []) {
            $log->warning('send_order_email.skipped_no_tickets', ['order_id' => $order->getId()->value()]);

            return $carry;
        }

        Mail::to($order->getEmail())->send(new OrderToPaid(
            $responses,
            $carry['firstTicketTypeId'] ?? Uuid::random(),
            $carry['comment'] ?? null,
            null,
        ));

        $log->info('send_order_email.sent', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
        ]);

        return $carry;
    }
}
