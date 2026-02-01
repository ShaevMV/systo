<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
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

    public function findByEmail(string $email): ?QuestionnaireTicketDto;
    /**
     * Получить определённую анкету
     */
    public function get(int $id): QuestionnaireTicketDto;

    public function cacheStatus(int $id, QuestionnaireStatus $questionnaireStatus): bool;
}
