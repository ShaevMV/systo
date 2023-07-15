<?php

declare(strict_types=1);

namespace Tickets\User\Account\Dto;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class UserInfoDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $city,
        protected string $phone,
        protected bool $admin,
        protected ?string $name = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['email'],
            $data['city'],
            $data['phone'],
            (bool) $data['is_admin'],
            $data['name'] ?? null
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function isRole(string $role): bool
    {
        return $this->$role ?? false;
    }
}
