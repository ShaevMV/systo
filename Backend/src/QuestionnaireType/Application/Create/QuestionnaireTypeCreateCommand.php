<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;

class QuestionnaireTypeCreateCommand implements Command
{
    public function __construct(
        private QuestionnaireTypeDto $data
    )
    {
    }

    public function getData(): QuestionnaireTypeDto
    {
        return $this->data;
    }
}
