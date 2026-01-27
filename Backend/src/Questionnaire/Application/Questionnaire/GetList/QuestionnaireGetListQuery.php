<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Application\Questionnaire\GetList;

use Shared\Domain\Bus\Query\Query;

class QuestionnaireGetListQuery implements Query
{
    public function __construct(
        private ?string $email = null,
        private ?string $telegram = null,
        private ?string $vk = null,
        private ?bool $is_have_in_club = null,
        private ?string $status = null,
    )
    {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getTelegram(): ?string
    {
        return $this->telegram;
    }

    public function getVk(): ?string
    {
        return $this->vk;
    }

    public function getIsHaveInClub(): ?bool
    {
        return $this->is_have_in_club;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
