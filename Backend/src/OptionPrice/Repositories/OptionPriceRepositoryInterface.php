<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\OptionPrice\Dto\OptionPriceDto;

interface OptionPriceRepositoryInterface
{
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): OptionPriceDto;

    public function create(OptionPriceDto $data): bool;

    public function editItem(Uuid $id, OptionPriceDto $data): bool;

    public function remove(Uuid $id): bool;
}
