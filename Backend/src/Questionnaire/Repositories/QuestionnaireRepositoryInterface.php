<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

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
     * Получить список всех анкет
     *
     * @param  Filters  $filters
     * @return QuestionnaireTicketDto[]
     */
    public function getList(Filters $filters): Collection;

    /**
     * Проверить наличие анкеты по email пользователя
     *
     * @param  string  $email
     */
    public function existByEmail(string $email): bool;

    public function findByEmail(?string $email): ?QuestionnaireTicketDto;

    /**
     * Получить определённую анкету
     */
    public function get(int $id): QuestionnaireTicketDto;

    /**
     * Найти анкету по orderId и ticketId
     */
    public function findByOrderIdAndTicketId(Uuid $orderId, Uuid $ticketId): ?QuestionnaireTicketDto;

    /**
     * Найти анкету по email и типу анкеты
     */
    public function findByEmailAndQuestionnaireType(string $email, Uuid $questionnaireTypeId): ?QuestionnaireTicketDto;

    public function cacheStatus(int $id, string $questionnaireStatus): bool;
}
