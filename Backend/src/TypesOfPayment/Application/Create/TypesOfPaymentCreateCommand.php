<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Create;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class TypesOfPaymentCreateCommand implements Command
{
    public function __construct(
        private TypesOfPaymentDto $paymentDto,
    )
    {
    }

    public function getPaymentDto(): TypesOfPaymentDto
    {
        return $this->paymentDto;
    }
}
