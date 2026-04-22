<?php

namespace Tickets\User\Account\Application\GetList;

class AccountGetListFilter
{
    public function __construct(
        private ?string $name = null,
        private ?string $email = null,
        private ?string $role = null,
        private ?string $phone = null,
        private ?string $city = null,
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            $data['email'] ?? null,
            $data['role'] ?? null,
            $data['phone'] ?? null,
            $data['city'] ?? null,
        );
    }
}
