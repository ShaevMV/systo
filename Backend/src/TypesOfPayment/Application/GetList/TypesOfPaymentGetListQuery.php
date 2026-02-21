<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\GetList;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentGetListQuery implements Query
{
    public function __construct(
        private ?string $name = null,
        private ?bool $active = null,
        private ?bool $isBilling = null,
        private ?Uuid $userExternal = null,
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
        return $this->userExternal;
    }
}
