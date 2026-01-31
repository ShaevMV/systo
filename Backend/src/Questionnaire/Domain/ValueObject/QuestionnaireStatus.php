<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Domain\ValueObject;

enum QuestionnaireStatus: string
{
    case NEW = 'NEW';
    case APPROVE = 'APPROVE';
}
