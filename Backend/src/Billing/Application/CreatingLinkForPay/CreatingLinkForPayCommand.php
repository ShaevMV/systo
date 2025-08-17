<?php

declare(strict_types=1);

namespace Tickets\Billing\Application\CreatingLinkForPay;

use Shared\Domain\Bus\Command\Command;
use Tickets\Billing\DTO\PaymentRequestDTO;
use Tickets\Billing\ValueObject\DeviceValueObject;

class CreatingLinkForPayCommand implements Command
{
    public function __construct(
        private PaymentRequestDTO $requestDTO,
        private DeviceValueObject $deviceValueObject,
    )
    {
    }

    public function getRequestDTO(): PaymentRequestDTO
    {
        return $this->requestDTO;
    }

    public function getDeviceValueObject(): DeviceValueObject
    {
        throw new \DomainException($this->deviceValueObject->getDevice());
        return $this->deviceValueObject;
    }
}
