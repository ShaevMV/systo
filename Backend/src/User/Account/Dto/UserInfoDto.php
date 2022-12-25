<?php

declare(strict_types=1);

namespace Tickets\User\Account\Dto;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class UserInfoDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected bool $admin,
        protected ?string $name = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['email'],
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
