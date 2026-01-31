<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetItem;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class QuestionnaireGetItemQuery implements Query
{
    public function __construct(
        private int $id,
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
