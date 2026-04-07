<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetQuestionnaireTypeByOrderTicket;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class GetQuestionnaireTypeByOrderTicketQuery implements Query
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
