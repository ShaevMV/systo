<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\GetPromoCodes;

use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\Bus\Query\QueryHandler;

class GetPromoCodeItemQueryHandler implements QueryHandler
{
    public function __construct(
        private PromoCodeInterface $promoCode
    )
    {
    }

    public function __invoke(GetPromoCodeItemQuery $codeItemQuery): PromoCodeDto
    {
        return $this->promoCode->getItem($codeItemQuery->getId());
    }
}
