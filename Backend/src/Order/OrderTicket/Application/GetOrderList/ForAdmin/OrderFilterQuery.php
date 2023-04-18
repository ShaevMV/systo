<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use Tickets\Shared\Domain\Bus\Query\Query;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderFilterQuery implements Query
{
    public function __construct(
        private ?Uuid $typesOfPayment = null,
        private ?string $email = null,
        private ?string $status = null,
        private ?string $promoCode = null,
        private ?float $price = null,
        private ?Uuid $typeOrder = null,
    ) {
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
        $typesOfPayment = $data['typesOfPayment'] ?? null;
        $typePrice = $data['typePrice'] ?? null;

        return new self(
            (null !== $typesOfPayment) ? new Uuid($data['typesOfPayment']) : null,
            $data['email'] ?? null,
            $data['status'] ?? null,
            $data['promoCode'] ?? null,
            $data['price'] ?? null,
            (null !== $typePrice) ? new Uuid($typePrice) : null,
        );
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function getTypeOrder(): ?Uuid
    {
        return $this->typeOrder;
    }
}
