<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderFilterQuery implements Query
{
    public function __construct(
        private ?Uuid $typeOrder = null,
        private ?Uuid $typesOfPayment = null,
        private ?string $email = null,
        private ?string $status = null,
        private ?string $promoCode = null,
    ) {
    }

    public function getTypeOrder(): ?Uuid
    {
        return $this->typeOrder;
    }

    public function getTypesOfPayment(): ?Uuid
    {
        return $this->typesOfPayment;
    }

    public function getEmail(): ?string
    {
        return null !== $this->email ? '%'.$this->email.'%' : null;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getPromoCode(): ?string
    {
        return null !== $this->promoCode ? '%'.$this->promoCode.'%' : null;
    }

    public static function fromState(array $data): self
    {
        $typeOrder = $data['typeOrder'] ?? null;
        $typesOfPayment = $data['typesOfPayment'] ?? null;

        return new self(
            (null !== $typeOrder) ? new Uuid($data['typeOrder']) : null,
            (null !== $typesOfPayment) ? new Uuid($data['typesOfPayment']) : null,
            $data['email'] ?? null,
            $data['status'] ?? null,
            $data['promoCode'] ?? null,
        );
    }
}
