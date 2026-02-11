<?php

declare(strict_types=1);

namespace Shared\Questionnaire\Application\Questionnaire\SendTelegram;

use Shared\Domain\Bus\Command\Command;

class SendTelegramCommand implements Command
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