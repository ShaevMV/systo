<?php

declare(strict_types=1);

namespace Tickets\User\Domain;

use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Uuid;


class Account extends AggregateRoot
{
    public function __construct(
        private Uuid $id,
        private string $email,
        private ?string $name = null,
    ) {
    }

    public static function creatingNewAccount(
        Uuid $uuid,
        string $email,
        string $password,
        ?string $name = null,
    ): self {
        $self = new self($uuid, $email, $name);
        $self->record(new ProcessAccountNotification($email, $password));

        return $self;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
