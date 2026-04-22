<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\GetByCode;

use Shared\Domain\Bus\Query\Query;

class QuestionnaireTypeGetByCodeQuery implements Query
{
    public function __construct(
        private string $code
    )
    {
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
