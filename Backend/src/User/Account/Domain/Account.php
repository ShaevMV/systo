<?php

declare(strict_types=1);

namespace Tickets\User\Account\Domain;

use Illuminate\Support\Str;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Dto\AccountDto;

class Account extends AggregateRoot
{
    public function __construct(
        private Uuid $id,
        private string $email,
        private string $phone,
        private string $city,
        private ?string $name = null,
    ) {
    }

    public static function creatingNewAccount(
        Uuid $uuid,
        AccountDto $accountDto,
        string $password,
    ): self {
        $self = new self(
            $uuid,
            $accountDto->getEmail(),
            $accountDto->getPhone(),
            $accountDto->getCity()
        );


        $self->record(new ProcessAccountNotification(
                $accountDto->getEmail(),
                $password,
            )
        );

        return $self;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
}
