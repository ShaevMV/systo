<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\SearchPromoCode;

use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

final class IsCorrectPromoCode
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        PromoCodeQueryHandler $codeQueryHandler
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            PromoCodeQuery::class => $codeQueryHandler
        ]);
    }

    public function findPromoCode(?string $name, float $price): PromoCodeDto
    {
        if (is_null($name)) {
            return new PromoCodeDto(new LimitPromoCodeDto());
        }

        /** @var PromoCodeDto $result */
        $result = $this->queryBus->ask(
            new PromoCodeQuery($name)
        );

        if($result->isPercent()) {
            return $result->setDiscount(
                ($price * ($result->getDiscount() / 100))
            );
        }

        return $result;
    }
}
