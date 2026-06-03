<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Application\Pricing\Response\OrderPriceQuote;
use Tickets\Order\OrderTicket\Application\Pricing\Response\OrderPriceQuoteLine;
use Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestLine;

/**
 * Handler для {@see CalculateOrderPriceQuery}.
 *
 * Адаптер: зовёт {@see OrderPriceCalculator::calculateLines()} и упаковывает результат
 * в read-модель {@see OrderPriceQuote} для фронта (live-preview).
 *
 * **Зачем отдельная read-модель**, а не `OrderGuestLine[]->toArray()`:
 * - Domain VO содержит `id`, `value`, `email` — это персональные данные/идентификаторы,
 *   не нужные на preview-этапе и шумящие в API.
 * - Фронту нужен только breakdown сумм — отдаём ровно его.
 */
class CalculateOrderPriceQueryHandler implements QueryHandler
{
    public function __construct(
        private OrderPriceCalculator $calculator,
    ) {
    }

    public function __invoke(CalculateOrderPriceQuery $query): OrderPriceQuote
    {
        $lines = $this->calculator->calculateLines(
            $query->getFestivalId(),
            $query->getRawGuests(),
        );

        $totalPrice = 0;
        $quoteLines = [];
        foreach ($lines as $line) {
            $quoteLines[] = $this->toQuoteLine($line);
            $totalPrice += $line->total()->amount();
        }

        return new OrderPriceQuote($quoteLines, $totalPrice);
    }

    private function toQuoteLine(OrderGuestLine $line): OrderPriceQuoteLine
    {
        $snapshot = $line->price;

        return new OrderPriceQuoteLine(
            basePrice: $snapshot->basePrice->amount(),
            optionsSum: $snapshot->optionsSum->amount(),
            discount: $snapshot->discount->amount(),
            total: $snapshot->total()->amount(),
        );
    }
}
