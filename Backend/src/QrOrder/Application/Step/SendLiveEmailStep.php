<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use App\Mail\OrderToLiveTicketIssued;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Письмо для живого заказа: уведомление о выдаче живого билета (OrderToLiveTicketIssued), БЕЗ PDF.
 * Одно письмо на заказ по типу билета первого гостя. Отправка — через MailDispatcher (Ф2).
 */
final class SendLiveEmailStep implements PipelineStepInterface
{
    public function __construct(
        private readonly MailDispatcher $dispatcher,
    ) {}

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

        $emailId = $this->dispatcher->send(
            EmailEvent::ORDER_PAID_LIVE,
            new EmailContext(
                recipient: $order->getEmail(),
                festivalId: $order->getFestivalId()?->value(),
                orderType: $order->getTypeOrder(),
                ticketTypeId: $ticketTypeId->value(),
                source: 'qr_pipeline',
                actorType: ActorType::QR,
                aggregateType: 'qr_order',
                aggregateId: $order->getId()->value(),
                meta: ['tickets' => count($responses)],
            ),
            new OrderToLiveTicketIssued($ticketTypeId),
        );

        $log->info('send_live_email.dispatched', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
            'email_id' => $emailId->value(),
        ]);

        return $carry;
    }
}
