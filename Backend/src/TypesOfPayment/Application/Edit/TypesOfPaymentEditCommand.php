<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Edit;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class TypesOfPaymentEditCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private TypesOfPaymentDto $paymentDto,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getPaymentDto(): TypesOfPaymentDto
    {
        return $this->paymentDto;
    }
}
