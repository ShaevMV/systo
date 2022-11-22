<?php

namespace Tickets\User\Domain;

use Tickets\Shared\Domain\Aggregate\AggregateRoot;

class Account extends AggregateRoot
{
    public function __construct(
        private string $email,
        private ?string $name = null,
    ) {
    }


    public static function creatingNewAccount(
        string $email,
        string $password,
        ?string $name = null,
    ): self {
        $self = new self($email, $name);
        $self->record(new AccountNewCreatingDomainEvent($email,$password));
    }
}
