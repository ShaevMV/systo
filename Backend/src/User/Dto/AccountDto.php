<?php

declare(strict_types = 1);

namespace Tickets\User\Dto;

use Hash;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class AccountDto extends AbstractionEntity
{
    protected Uuid $id;
    protected string $password;

    public function __construct(
        protected string $email,
        string $password,
        protected ?string $name = null,
        ?Uuid $id = null,
    ) {
        $this->password = Hash::make($password);
        $this->id = $id ?? Uuid::random();
    }

    public static function fromState(array $data): self
    {
        $id = !is_null($data['id']) ? new Uuid($data['id']) : null;

        return new self(
            $data['email'],
            $data['password'],
            $data['name'] ?? null,
            $id
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
