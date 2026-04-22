<?php

declare(strict_types=1);

namespace Tickets\User\Account\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class UserInfoDto extends AbstractionEntity implements Response
{
    public function __construct(
        public Uuid $id,
        public string $email,
        public string $city,
        public string $role,
        public ?string $phone = null,
        public ?string $name = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['email'],
            $data['city'] ?? '',
            $data['role'],
            $data['phone'] ?? null,
            $data['name'] ?? null
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }
}
