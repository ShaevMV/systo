<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetByOrderTicket;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class QuestionnaireGetByOrderTicketQuery implements Query
{
    public function __construct(
        private Uuid $orderId,
        private Uuid $ticketId,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getTicketId(): Uuid
    {
        return $this->ticketId;
    }
}
