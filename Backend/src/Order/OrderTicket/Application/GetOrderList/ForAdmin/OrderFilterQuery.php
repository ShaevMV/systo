<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use InvalidArgumentException;
use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class OrderFilterQuery implements Query
{
    public function __construct(
        private Uuid $festivalId,
        private ?Uuid $typesOfPayment = null,
        private ?string $email = null,
        private ?string $status = null,
        private ?string $promoCode = null,
        private ?float $price = null,
        private ?Uuid $typeOrder = null,
        private bool $isManager = false,
        private ?string $city = null,
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

    public static function fromState(array $data, bool $isManager): self
    {
        if(!isset($data['festivalId'])) {
            throw new InvalidArgumentException('festivalId обязательное поле!');
        }

        $typesOfPayment = $data['typesOfPayment'] ?? null;
        $typePrice = $data['typePrice'] ?? null;
        return new self(
            new Uuid($data['festivalId']),
            (null !== $typesOfPayment) ? new Uuid($data['typesOfPayment']) : null,
            $data['email'] ?? null,
            $data['status'] ?? null,
            $data['promoCode'] ?? null,
            $data['price'] ?? null,
            (null !== $typePrice) ? new Uuid($data['typePrice']) : null,
            $isManager,
            $data['city'] ?? null,
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

    public function getActive(): string
    {
        return $this->active ?? '1';
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festivalId;
    }

    public function isManager(): bool
    {
        return $this->isManager;
    }

    public function getCity(): ?string
    {
        return null !== $this->city ? '%'.$this->city.'%' : null;
    }
}
