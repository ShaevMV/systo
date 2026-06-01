<?php

declare(strict_types=1);

namespace Tickets\Option\Application\GetItem;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Repositories\OptionRepositoryInterface;

class OptionGetItemQueryHandler implements QueryHandler
{
    public function __construct(
        private OptionRepositoryInterface $repository
    ) {
    }

    public function __invoke(OptionGetItemQuery $query): OptionDto
    {
        return $this->repository->getItem($query->getId());
    }
}
