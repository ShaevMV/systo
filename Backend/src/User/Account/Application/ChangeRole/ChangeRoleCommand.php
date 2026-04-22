<?php

namespace Tickets\User\Account\Application\ChangeRole;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class ChangeRoleCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private string $role,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
