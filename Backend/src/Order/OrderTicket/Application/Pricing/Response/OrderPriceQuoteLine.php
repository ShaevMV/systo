<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

/**
 * Детализация цены одной строки (одного гостя) для preview на фронте.
 *
 * Все суммы в **целых рублях** (тип `int`) — формат `Money::amount()`.
 * Не содержит идентификаторов и персональных данных, потому что preview
 * считается до сохранения и без `id` строк.
 */
final class OrderPriceQuoteLine extends AbstractionEntity implements Response
{
    public function __construct(
        protected int $basePrice,
        protected int $optionsSum,
        protected int $discount,
        protected int $total,
    ) {
    }

    public function getBasePrice(): int
    {
        return $this->basePrice;
    }

    public function getOptionsSum(): int
    {
        return $this->optionsSum;
    }

    public function getDiscount(): int
    {
        return $this->discount;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
