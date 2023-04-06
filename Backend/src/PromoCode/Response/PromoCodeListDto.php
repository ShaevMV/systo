<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;

class PromoCodeListDto extends AbstractionEntity implements Response
{
    /**
     * @param PromoCodeDto[] $listPromoCode
     */
    public function __construct(
        protected array $listPromoCode
    )
    {
    }

    public function getListPromoCode(): array
    {
        return $this->listPromoCode;
    }
}