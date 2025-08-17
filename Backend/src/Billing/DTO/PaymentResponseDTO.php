<?php

declare(strict_types=1);

namespace Tickets\Billing\DTO;

use Shared\Infrastructure\Bus\Command\CommandResponse;

class PaymentResponseDTO implements CommandResponse
{
    public function __construct(
        private string $linkToReceipt,
        private string $status,
        private ?string $error = null,
    )
    {
    }

    /**
     * @return string
     */
    public function getLinkToReceipt(): string
    {
        return $this->linkToReceipt;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }
}
