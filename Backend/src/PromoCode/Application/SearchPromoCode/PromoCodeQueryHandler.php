<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\SearchPromoCode;

use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\PromoCode\Response\PromoCodeDto;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

final class PromoCodeQueryHandler implements QueryHandler
{
    public function __construct(
        private PromoCodeInterface $promoCode
    ) {
    }

    public function __invoke(PromoCodeQuery $query): PromoCodeDto
    {
        return $this->promoCode->find($query->getName()) ?? new PromoCodeDto();
    }
}
