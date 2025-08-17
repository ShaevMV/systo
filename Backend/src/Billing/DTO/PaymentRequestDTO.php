<?php

namespace Tickets\Billing\DTO;

use Shared\Domain\ValueObject\Uuid;

class PaymentRequestDTO
{
    public function __construct(
        private Uuid   $orderId,
        private int    $price,
        private int    $quantity,
        private string $email,
        private string $phone,
        private string $label = 'Взнос на туристический слёт',
    )
    {
    }


    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function toArray(): array
    {
        return [
            "amount" => $this->price * $this->quantity,
            "client_payment_id" => $this->orderId->value(),
            "metadata" => [
                "order_id" => $this->orderId->value(),
            ],
            "method" => "sbp",
            "client_receipt" => [
                "customer_email" => "shaevmv@gmail.com",
                "taxation_system" => 1,
                "items" => [
                    [
                        "label" => $this->label,
                        "quantity" => $this->quantity,
                        "price" => $this->price,
                        "vat" => 6
                    ],
                ],
            ],
        ];
    }
}
