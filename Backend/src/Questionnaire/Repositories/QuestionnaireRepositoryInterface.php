<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

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
     * @return QuestionnaireGetListQueryResponse
     */
    public function getByOrderId(
        Uuid $orderId,
        ?Uuid $ticketId,
    ): QuestionnaireGetListQueryResponse;

    /**
     * Получить список всех анкет
     *
     * @param Filters $filters
     * @return QuestionnaireTicketDto[]
     */
    public function getList(Filters $filters): array;

    /**
     * Проверить наличие анкеты по email пользователя
     *
     * @param string $email
     */
    public function existByEmail(string $email): bool;
}
