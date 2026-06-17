<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Step;

use App\Mail\OrderListApproved;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\History\Domain\ActorType;
use Tickets\QrOrder\Application\Support\PipelineLog;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Шаг письма для заказа-списка: одно письмо получателю (order.email) со всеми PDF-билетами —
 * Mailable OrderListApproved (берёт blade-шаблон письма из Location.email_template).
 *
 * Требует festival_id (обязателен) + location_id (из order_data.location.id). Отправка — через
 * MailDispatcher (Ф2, трекинг + асинхронный SendEmailJob).
 */
final class SendListEmailStep implements PipelineStepInterface
{
    public function __construct(
        private readonly MailDispatcher $dispatcher,
    ) {
    }

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

        $emailId = $this->dispatcher->send(
            EmailEvent::LIST_APPROVED,
            new EmailContext(
                recipient: $order->getEmail(),
                festivalId: $festivalId->value(),
                orderType: $order->getTypeOrder(),
                source: 'qr_pipeline',
                actorType: ActorType::QR,
                aggregateType: 'qr_order',
                aggregateId: $order->getId()->value(),
                meta: ['tickets' => count($responses), 'location_id' => $locationId?->value()],
            ),
            new OrderListApproved($responses, $festivalId, $locationId),
        );

        $log->info('send_list_email.dispatched', [
            'order_id' => $order->getId()->value(),
            'to' => PipelineLog::maskEmail($order->getEmail()),
            'tickets' => count($responses),
            'location_id' => $locationId?->value(),
            'email_id' => $emailId->value(),
        ]);

        return $carry;
    }
}
