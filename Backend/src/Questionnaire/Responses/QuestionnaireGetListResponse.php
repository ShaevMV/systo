<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Responses;

use Shared\Domain\Collection;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireGetListResponse extends Collection
{
    protected function type(): string
    {
        return QuestionnaireTicketDto::class;
    }
}
