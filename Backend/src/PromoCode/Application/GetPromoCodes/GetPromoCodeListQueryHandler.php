<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\GetPromoCodes;

use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\PromoCode\Response\PromoCodeListDto;
use Shared\Domain\Bus\Query\QueryHandler;

class GetPromoCodeListQueryHandler implements QueryHandler
{
    public function __construct(
        private PromoCodeInterface $promoCode
    )
    {
    }

    public function __invoke(GetPromoCodeListQuery $query): PromoCodeListDto
    {
        $listPromoCodeDto = $this->promoCode->getList();

        return new PromoCodeListDto($listPromoCodeDto);
    }
}
