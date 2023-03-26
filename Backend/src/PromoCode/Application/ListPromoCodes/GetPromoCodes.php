<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ListPromoCodes;

use Tickets\PromoCode\Application\SearchPromoCode\PromoCodeQuery;
use Tickets\PromoCode\Response\PromoCodeListDto;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetPromoCodes
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        private GetPromoCodeListQueryHandler $getPromoCodeListQueryHandler
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPromoCodeListQuery::class => $this->getPromoCodeListQueryHandler,
        ]);
    }

    public function getList(): PromoCodeListDto
    {
        /** @var PromoCodeListDto $promoCodeListDto */
        $promoCodeListDto = $this->queryBus->ask(new GetPromoCodeListQuery());

        return $promoCodeListDto;
    }
}
