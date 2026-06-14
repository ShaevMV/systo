<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Illuminate\Support\Facades\Mail;
use Tickets\QrOrder\Application\Support\OrderEmailResolver;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг 2: одно письмо получателю (order.email) со всеми PDF-билетами заказа.
 *
 * Mailable выбирается по type_order через OrderEmailResolver (regular → OrderToPaid,
 * friendly → OrderToPaidFriendly). PDF и blade-шаблон (парковка/детский) рендерятся внутри
 * письма из TicketResponse — шаг НЕ зависит от завершения ProcessCreatingQRCode.
 */
final class SendOrderEmailStep implements PipelineStepInterface
{
    public function __construct(
        private readonly OrderEmailResolver $emailResolver,
    ) {
    }

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

        $mailable = $this->emailResolver->resolve(
            $order->getTypeOrder(),
            $responses,
            $carry['firstTicketTypeId'] ?? null,
            $carry['comment'] ?? null,
        );

        Mail::to($order->getEmail())->send($mailable);

        $log->info('send_order_email.sent', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
            'type_order' => $order->getTypeOrder(),
            'mailable' => get_class($mailable),
        ]);

        return $carry;
    }
}
