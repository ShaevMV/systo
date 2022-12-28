<?php

declare(strict_types = 1);

namespace Tickets\Ordering\InfoForOrder\Application\SearchPromoCode;

use Tickets\Ordering\InfoForOrder\Repositories\PromoCodeInterface;
use Tickets\Ordering\InfoForOrder\Response\PromoCodeDto;
use Tickets\Shared\Domain\Bus\Query\QueryHandler;

final class PromoCodeQueryHandler implements QueryHandler
{
    public function __construct(
        private PromoCodeInterface $promoCod
    ) {
    }

    public function __invoke(PromoCodeQuery $query): ?PromoCodeDto
    {
        return $this->promoCod->find($query->getName());
    }
}
