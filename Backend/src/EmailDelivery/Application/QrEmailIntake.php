<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application;

use App\Mail\GenericTemplatedMail;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\TemplateBinding\Application\TemplateBindingApplication;

/**
 * Приём НЕ-заказного письма от витрины qr (Ф4): общая логика для HTTP-канала
 * (EmailNotificationController) и для AMQP-консьюмера (routing key qr.email.send) —
 * чтобы не дублировать. Идемпотентность по external_id. source = qr_intake.
 *
 * НЕ final намеренно — мокается в юнит-тестах консьюмера (QrInboundMessageHandler).
 */
class QrEmailIntake
{
    public function __construct(
        private readonly MailDispatcher $dispatcher,
        private readonly TemplateBindingApplication $bindings,
        private readonly EmailMessageRepositoryInterface $repository,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data  контракт письма (event/email/vars/external_id/...)
     * @return array{status: string, message?: string, email_id?: string}
     *                                       status: accepted | duplicate | invalid
     */
    public function ingest(array $data): array
    {
        $event = (string) ($data['event'] ?? '');
        if (! EmailEvent::isValid($event)) {
            return ['status' => 'invalid', 'message' => 'Неизвестное событие письма'];
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return ['status' => 'invalid', 'message' => 'Не передан email получателя'];
        }

        $externalId = isset($data['external_id']) ? (string) $data['external_id'] : null;
        if ($externalId !== null && $this->repository->existsByExternalId($externalId)) {
            return ['status' => 'duplicate', 'message' => 'Уже принято ранее (идемпотентно)'];
        }

        $vars = is_array($data['vars'] ?? null) ? $data['vars'] : [];
        $festivalId = isset($data['festival_id']) ? (string) $data['festival_id'] : null;
        $orderType = isset($data['order_type']) ? (string) $data['order_type'] : null;
        $ticketTypeId = isset($data['ticket_type_id']) ? (string) $data['ticket_type_id'] : null;

        $slug = $this->bindings->resolveSlug('email', $event, $festivalId, $orderType, $ticketTypeId)
            ?? EmailEvent::defaultSlug($event);

        $subject = (string) ($data['subject'] ?? $vars['subject'] ?? 'Уведомление');

        $emailId = $this->dispatcher->send(
            $event,
            new EmailContext(
                recipient: $email,
                festivalId: $festivalId,
                orderType: $orderType,
                ticketTypeId: $ticketTypeId,
                source: 'qr_intake',
                actorType: ActorType::QR,
                aggregateType: isset($data['aggregate_id']) ? 'qr_order' : null,
                aggregateId: isset($data['aggregate_id']) ? (string) $data['aggregate_id'] : null,
                meta: $externalId !== null ? ['external_id' => $externalId] : [],
            ),
            new GenericTemplatedMail((string) $slug, $subject, $vars),
        );

        return ['status' => 'accepted', 'email_id' => $emailId->value()];
    }
}
