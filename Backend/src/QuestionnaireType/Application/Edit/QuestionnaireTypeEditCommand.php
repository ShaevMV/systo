<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;

class QuestionnaireTypeEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private QuestionnaireTypeDto $data
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getData(): QuestionnaireTypeDto
    {
        return $this->data;
    }
}
