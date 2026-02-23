<?php

namespace Tickets\User\Account\Application\ChanceRole;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

class ChanceRoleCommand implements Command
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
