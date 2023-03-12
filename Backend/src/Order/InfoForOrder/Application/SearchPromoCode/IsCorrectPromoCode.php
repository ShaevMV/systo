<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Application\SearchPromoCode;

use Tickets\Order\InfoForOrder\Response\PromoCodeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

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
            return new PromoCodeDto();
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
