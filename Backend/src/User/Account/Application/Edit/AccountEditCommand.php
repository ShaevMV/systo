<?php

declare(strict_types=1);

namespace Tickets\User\Account\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Dto\UserInfoDto;

class AccountEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private UserInfoDto $userInfoDto,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserInfoDto(): UserInfoDto
    {
        return $this->userInfoDto;
    }
}
