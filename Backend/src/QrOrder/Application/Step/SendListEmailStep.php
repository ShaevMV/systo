<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use App\Mail\OrderListApproved;
use Illuminate\Support\Facades\Mail;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг письма для заказа-списка: одно письмо получателю (order.email) со всеми PDF-билетами —
 * Mailable OrderListApproved (берёт blade-шаблон письма из Location.email_template).
 *
 * Требует festival_id (обязателен) + location_id (из order_data.location.id). PDF рендерится
 * внутри письма из TicketResponse, поэтому шаг не зависит от завершения ProcessCreatingQRCode.
 */
final class SendListEmailStep implements PipelineStepInterface
{
    public function name(): string
    {
        return 'send_list_email';
    }

    public function handle(QrOrderDto $order, array $carry): array
    {
        $responses = $carry['responses'] ?? [];
        $log = PipelineLog::logger();

        if ($responses === []) {
            $log->warning('send_list_email.skipped_no_tickets', ['order_id' => $order->getId()->value()]);

            return $carry;
        }

        $festivalId = $order->getFestivalId();
        if ($festivalId === null) {
            $log->error('send_list_email.no_festival', ['order_id' => $order->getId()->value()]);

            return $carry;
        }

        $location = is_array($order->getPayload()['order_data']['location'] ?? null)
            ? $order->getPayload()['order_data']['location']
            : [];
        $locationId = empty($location['id']) ? null : new Uuid((string) $location['id']);

        Mail::to($order->getEmail())->send(new OrderListApproved($responses, $festivalId, $locationId));

        $log->info('send_list_email.sent', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
            'location_id' => $locationId?->value(),
        ]);

        return $carry;
    }
}
