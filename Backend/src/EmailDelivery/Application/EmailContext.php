<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application;

use Tickets\History\Domain\ActorType;

/**
 * Контекст отправки письма для MailDispatcher: получатель + ключи привязки шаблона по событию
 * (festival/order_type/ticket_type) + источник/агрегат для админ-экрана «Доставка писем».
 *
 * vars/attachments не нужны — конкретный Mailable их уже несёт; контекст лишь описывает
 * «кому, по какому поводу и от чьего имени» для трекинга и резолва привязки.
 */
final class EmailContext
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public readonly string $recipient,
        public readonly ?string $festivalId = null,
        public readonly ?string $orderType = null,
        public readonly ?string $ticketTypeId = null,
        public readonly string $source = 'org_event',
        public readonly string $actorType = ActorType::SYSTEM,
        public readonly ?string $aggregateType = null,
        public readonly ?string $aggregateId = null,
        public readonly array $meta = [],
    ) {
    }
}
