<?php

declare(strict_types=1);

namespace Tickets\User\Account\Dto;

use Illuminate\Support\Facades\Hash;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\User\Account\Helpers\AccountRoleHelper;

final class AccountDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $phone,
        protected string $city,
        protected ?string $name = null,
        protected bool $is_admin = false,
        protected string $role = AccountRoleHelper::guest,
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
            $data['name'] ?? null,
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
        return $this->is_admin;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}
