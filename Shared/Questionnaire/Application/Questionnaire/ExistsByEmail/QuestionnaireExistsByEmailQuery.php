<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\ExistsByEmail;

use Shared\Domain\Bus\Query\Query;

class QuestionnaireExistsByEmailQuery implements Query
{
    public function __construct(
        private string $email
    )
    {
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
