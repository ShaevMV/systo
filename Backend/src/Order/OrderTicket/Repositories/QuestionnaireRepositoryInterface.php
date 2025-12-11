<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;
use Tickets\Order\OrderTicket\Responses\QuestionnaireGetItemQueryResponse;

interface QuestionnaireRepositoryInterface
{
    /**
     * Создать Анкету
     *
     * @param  QuestionnaireTicketDto  $questionnaireTicketDto
     * @return bool
     */
    public function create(QuestionnaireTicketDto $questionnaireTicketDto): bool;

    /**
     * Подучить заполненную анкету по номеру заказа
     *
     * @param Uuid $orderId
     * @return QuestionnaireGetItemQueryResponse|null
     */
    public function getByOrderId(Uuid $orderId): ?QuestionnaireGetItemQueryResponse;
}
