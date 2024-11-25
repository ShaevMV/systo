<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

final class ListTypesOfPaymentDto extends AbstractionEntity implements Response
{
    /**
     * @param TypesOfPaymentDto[] $typesOfPaymentDto
     */
    public function __construct(
        protected array $typesOfPaymentDto
    )
    {
    }

    public function getTypesOfPaymentDto(): array
    {
        return $this->typesOfPaymentDto;
    }
}
