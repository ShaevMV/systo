<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetList;

use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentGetListFilter
{
    public function __construct(
        private ?string $name = null,
        private ?bool $active = null,
        private ?bool $isBilling = null,
        private ?Uuid $userExternalId = null,
    )
    {
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function getIsBilling(): ?bool
    {
        return $this->isBilling;
    }

    public function getUserExternal(): ?Uuid
    {
        return $this->userExternalId;
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['name'] ?? null,
            empty($data['active']) ? null : $data['active'],
            empty($data['isBilling']) ? null : $data['isBilling'],
            empty($data['userExternalId']) ? null : new Uuid($data['userExternalId']),
        );
    }
}
