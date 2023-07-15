<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Repositories;

use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\ValueObject\Uuid;

interface PromoCodeInterface
{
    public function find(string $name): ?PromoCodeDto;

    /**
     * @return PromoCodeDto[]
     */
    public function getList(): array;

    public function getItem(Uuid $id): ?PromoCodeDto;

    public function createOrUpdate(PromoCodeDto $promoCodeDto): bool;
}
