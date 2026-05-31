<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Application\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\OptionPrice\Dto\OptionPriceDto;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;

class OptionPriceGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OptionPriceRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionPriceGetItemQuery $query): OptionPriceDto
    {
        return $this->repository->getItem($query->getId());
    }
}
