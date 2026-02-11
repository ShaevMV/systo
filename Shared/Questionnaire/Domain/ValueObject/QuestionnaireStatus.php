<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Domain\ValueObject;

enum QuestionnaireStatus: string
{
    case NEW = 'NEW';
    case APPROVE = 'APPROVE';
}
