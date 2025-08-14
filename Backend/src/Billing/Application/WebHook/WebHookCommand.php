<?php

declare(strict_types=1);

namespace Tickets\Billing\Application\WebHook;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Billing\ValueObject\StatusForBillingValueObject;

class WebHookCommand implements Command
{
    public function __construct(
        private Uuid $orderId,
        private StatusForBillingValueObject $status,
        private ?string $linkToReceipt = null,
    )
    {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getStatus(): StatusForBillingValueObject
    {
        return $this->status;
    }

    public function getLinkToReceipt(): ?string
    {
        return $this->linkToReceipt;
    }
}
