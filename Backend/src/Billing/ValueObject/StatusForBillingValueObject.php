<?php

declare(strict_types=1);

namespace Tickets\Billing\ValueObject;

class StatusForBillingValueObject
{
    // оплата прошла
    private const PAYMENT_COMPLETED = 'payment.completed';

    // Возврат
    private const PAYMENT_REFUND = 'payment.refund';

    public function __construct(
        private string $status
    )
    {
    }

    public function getStatus(): string
    {
        return $this->status;
    }


    public function isPaymentCompleted(): bool
    {
        return $this->status === self::PAYMENT_COMPLETED;
    }

    public function isPaymentRefund(): bool
    {
        return $this->status === self::PAYMENT_REFUND;
    }
}
