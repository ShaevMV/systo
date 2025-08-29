<?php

namespace Tickets\PromoCode\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

final class ExternalPromoCodeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected ?string $promocode = null
    )
    {
    }

    public function getPromocode(): ?string
    {
        return $this->promocode;
    }
}
