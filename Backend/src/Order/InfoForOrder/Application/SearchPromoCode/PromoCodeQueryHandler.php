<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\SearchPromoCode;

use Tickets\Order\InfoForOrder\Repositories\PromoCodeInterface;
use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Order\InfoForOrder\Response\PromoCodeDto;
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
