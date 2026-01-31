<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class QuestionnaireGetItemQuery implements Query
{
    public function __construct(
        private Uuid $orderId,
        private ?Uuid $ticketId = null,
    )
    {
    }

    public function getOrderId(): ?Uuid
    {
        return $this->orderId;
    }

    public function getTicketId(): ?Uuid
    {
        return $this->ticketId;
    }
}
