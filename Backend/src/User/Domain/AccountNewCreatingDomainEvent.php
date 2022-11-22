<?php
declare(strict_types = 1);

namespace Tickets\User\Domain;

use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;

final class AccountNewCreatingDomainEvent extends DomainEvent
{
    public function __construct(
        public string $email,
        public string $password
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['email'],
            $data['password'],
        );
    }
}
