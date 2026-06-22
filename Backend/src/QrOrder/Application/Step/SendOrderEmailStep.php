<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\OrderEmailResolver;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг 2: одно письмо получателю (order.email) со всеми PDF-билетами заказа.
 *
 * Mailable выбирается по type_order через OrderEmailResolver (regular → OrderToPaid,
 * friendly → OrderToPaidFriendly). Отправка идёт через MailDispatcher (Ф2): письмо ставится
 * в email_messages с трекингом и уходит асинхронным SendEmailJob — поэтому в пайплайне шаг
 * лишь ставит письмо в очередь, а статус (sent/failed) виден в экране «Доставка писем».
 */
final class SendOrderEmailStep implements PipelineStepInterface
{
    public function __construct(
        private readonly OrderEmailResolver $emailResolver,
        private readonly MailDispatcher $dispatcher,
    ) {}

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

        // Номер заказа qr (external_order_no) → в письмо как {{ kilter }} (как в order_created).
        $externalOrderNo = (int) ($order->getExternalOrderNo() ?? 0);

        $mailable = $this->emailResolver->resolve(
            $order->getTypeOrder(),
            $responses,
            $carry['firstTicketTypeId'] ?? null,
            $carry['comment'] ?? null,
            $externalOrderNo > 0 ? $externalOrderNo : null,
        );

        $event = TypeOrder::normalize($order->getTypeOrder()) === TypeOrder::FRIENDLY
            ? EmailEvent::ORDER_PAID_FRIENDLY
            : EmailEvent::ORDER_PAID;

        $emailId = $this->dispatcher->send(
            $event,
            new EmailContext(
                recipient: $order->getEmail(),
                festivalId: $order->getFestivalId()?->value(),
                orderType: $order->getTypeOrder(),
                ticketTypeId: ($carry['firstTicketTypeId'] ?? null)?->value(),
                source: 'qr_pipeline',
                actorType: ActorType::QR,
                aggregateType: 'qr_order',
                aggregateId: $order->getId()->value(),
                meta: ['tickets' => count($responses)],
            ),
            $mailable,
        );

        $log->info('send_order_email.dispatched', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
            'type_order' => $order->getTypeOrder(),
            'email_id' => $emailId->value(),
        ]);

        return $carry;
    }
}
