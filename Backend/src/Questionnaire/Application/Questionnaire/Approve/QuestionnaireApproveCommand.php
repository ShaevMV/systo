<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\Approve;

use Shared\Domain\Bus\Command\Command;

class QuestionnaireApproveCommand implements Command
{
    public function __construct(
        private int $id
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
