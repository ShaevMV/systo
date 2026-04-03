<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
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
     * @param Filters $filters
     * @return QuestionnaireTicketDto[]
     */
    public function getList(Filters $filters): Collection;

    /**
     * Проверить наличие анкеты по email пользователя
     *
     * @param string $email
     */
    public function existByEmail(string $email): bool;

    public function findByEmail(string $email): ?QuestionnaireTicketDto;
    /**
     * Получить определённую анкету
     */
    public function get(int $id): QuestionnaireTicketDto;

    public function cacheStatus(int $id, string $questionnaireStatus): bool;
}
