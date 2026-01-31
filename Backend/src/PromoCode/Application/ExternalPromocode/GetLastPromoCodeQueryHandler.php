<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ExternalPromocode;

use Tickets\PromoCode\Repositories\ExternalPromoCodeInterface;
use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Shared\Domain\Bus\Query\QueryHandler;

final class GetLastPromoCodeQueryHandler implements QueryHandler
{
    public function __construct(
        private ExternalPromoCodeInterface $promoCode
    ) {
    }

    public function __invoke(GetLastPromoCodeQuery $query): ?ExternalPromoCodeDto
    {
        return $this->promoCode->find($query->getOrderId());
    }
}
