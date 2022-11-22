<?php

declare(strict_types = 1);

namespace Tickets\Ordering\InfoForOrder\Application\SearchPromoCode;

use Tickets\Ordering\InfoForOrder\Response\PromoCodeDto;
use Tickets\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

final class IsCorrectPromoCode
{
    private InMemorySymfonyQueryBus $queryBus;

    public function __construct(
        PromoCodeQueryHandler $codeQueryHandler
    ){
        $this->queryBus = new InMemorySymfonyQueryBus([
           PromoCodeQuery::class => $codeQueryHandler
        ]);
    }

    public function findPromoCode(string $name): ?PromoCodeDto
    {
        /** @var PromoCodeDto|null $result */
        $result = $this->queryBus->ask(new PromoCodeQuery($name));

        return $result;
    }
}
