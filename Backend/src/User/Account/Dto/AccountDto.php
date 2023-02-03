<?php

declare(strict_types=1);

namespace Tickets\User\Account\Dto;

use Illuminate\Support\Facades\Hash;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class AccountDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $phone,
        protected string $city,
        protected bool $isAdmin = false,
    ) {
    }

    public static function fromState(array $data): self
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : Uuid::random();

        return new self(
            $id,
            $data['email'],
            $data['phone'] ?? '',
            $data['city'] ?? '',
            $data['isAdmin'] ?? false
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
