<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Application\Delete;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

class TypesOfPaymentDeleteCommand implements Command
{
    public function __construct(
        private Uuid $id,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }
}
