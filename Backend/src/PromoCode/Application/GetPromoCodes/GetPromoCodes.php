<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\GetPromoCodes;

use Tickets\PromoCode\Response\PromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeListDto;
use Tickets\Shared\Domain\Bus\Query\QueryBus;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetPromoCodes
{
    private QueryBus $queryBus;

    public function __construct(
        private GetPromoCodeListQueryHandler $getPromoCodeListQueryHandler,
        private GetPromoCodeItemQueryHandler $getPromoCodeItemQueryHandler,
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetPromoCodeListQuery::class => $this->getPromoCodeListQueryHandler,
            GetPromoCodeItemQuery::class => $this->getPromoCodeItemQueryHandler,
        ]);
    }

    public function getList(): PromoCodeListDto
    {
        /** @var PromoCodeListDto $promoCodeListDto */
        $promoCodeListDto = $this->queryBus->ask(new GetPromoCodeListQuery());

        return $promoCodeListDto;
    }

    public function getItem(Uuid $id): ?PromoCodeDto
    {
        /** @var PromoCodeDto|null $promoCodeDto */
        $promoCodeDto = $this->queryBus->ask(new GetPromoCodeItemQuery($id));

        return $promoCodeDto;
    }
}
