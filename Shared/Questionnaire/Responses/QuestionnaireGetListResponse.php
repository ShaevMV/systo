<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Collection;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireGetListResponse extends Collection implements Response
{
    protected function type(): string
    {
        return QuestionnaireTicketDto::class;
    }
}
