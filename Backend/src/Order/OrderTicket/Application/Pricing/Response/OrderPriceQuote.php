<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

/**
 * Результат расчёта цены заказа без его создания.
 *
 * Используется фронтом для live-preview на форме покупки. Содержит:
 * - `$lines`: детализация по каждой строке (одна на гостя) в том же порядке что и `RawGuestInput[]`
 * - `$totalPrice`: сумма `total` по всем строкам — финальная цена к оплате
 *
 * Все суммы — **целые рубли** (`Money::amount()`).
 */
final class OrderPriceQuote extends AbstractionEntity implements Response
{
    /**
     * @param  OrderPriceQuoteLine[]  $lines
     */
    public function __construct(
        protected array $lines,
        protected int $totalPrice,
    ) {
    }

    /**
     * @return OrderPriceQuoteLine[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }
}
