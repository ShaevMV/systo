<?php

declare(strict_types = 1);

namespace Tickets\User\Account\Dto;

use Illuminate\Support\Facades\Hash;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class AccountDto extends AbstractionEntity
{
    protected Uuid $id;
    protected ?string $password;

    public function __construct(
        protected string $email,
        ?string $password = null,
        protected ?string $name = null,
        ?Uuid $id = null,
    ) {
        $this->password = is_null($password) ? null : Hash::make($password);
        $this->id = $id ?? Uuid::random();
    }

    public static function fromState(array $data): self
    {
        $id = !is_null($data['id']) ? new Uuid($data['id']) : null;

        return new self(
            $data['email'],
            $data['password'] ?? null,
            $data['name'] ?? null,
            $id
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
