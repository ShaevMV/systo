<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing;

use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestInput;

/**
 * Query на расчёт цены заказа без его создания (live-preview для фронта).
 *
 * Используется на форме покупки `BuyTicket.vue` для мгновенного отображения цены
 * при изменении опций/промокода/количества гостей. Не создаёт записей в БД,
 * не пишет историю, не публикует Domain Events.
 *
 * Под капотом делегирует {@see OrderPriceCalculator::calculateLines()} и
 * собирает компактный {@see Response\OrderPriceQuote}.
 */
class CalculateOrderPriceQuery implements Query
{
    /**
     * @param  RawGuestInput[]  $rawGuests  непустой массив сырых гостей из payload
     */
    public function __construct(
        private Uuid $festivalId,
        private array $rawGuests,
    ) {
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }

    /**
     * @return RawGuestInput[]
     */
    public function getRawGuests(): array
    {
        return $this->rawGuests;
    }
}
