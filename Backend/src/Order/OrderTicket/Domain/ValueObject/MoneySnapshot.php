<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Money;

/**
 * MoneySnapshot — иммутабельный снимок цены конкретной строки заказа (`OrderGuestLine`).
 *
 * Хранит детализацию цены **на момент покупки**:
 * - `basePrice` — цена билета по текущей волне `ticket_type_price`
 * - `optionsSum` — сумма всех опций гостя (`OrderGuestOption[]`)
 * - `discount` — скидка по промокоду
 *
 * Итог считается через {@see self::total()} как `basePrice + optionsSum - discount`
 * (с клампом к 0 в `Money::subtract()` — если скидка больше суммы, итог 0).
 *
 * Снапшот сохраняется в `order_tickets.guests[].price_snapshot` (JSON-поле) при создании
 * заказа и больше **не пересчитывается**. Это защищает от смены волны цен или скидки
 * промокода после оплаты — отчёты и письма всегда видят ту сумму, что была на момент покупки.
 *
 * См. `.claude/specs/order-format-architecture.md` §1.2, §2.3, §5.
 *
 * Источник: Чистая архитектура (Р. Мартин), гл. «Сущности» — VO для денежной детализации;
 * Совершенный код, гл. «Классы» — VO неизменяем, любое изменение порождает новый объект.
 */
final class MoneySnapshot
{
    public function __construct(
        public readonly Money $basePrice,
        public readonly Money $optionsSum,
        public readonly Money $discount,
    ) {
    }

    public static function zero(): self
    {
        return new self(Money::zero(), Money::zero(), Money::zero());
    }

    /**
     * Итог: цена билета + сумма опций - скидка. Клампится к 0 если скидка > (base + options).
     */
    public function total(): Money
    {
        return $this->basePrice
            ->add($this->optionsSum)
            ->subtract($this->discount);
    }

    /**
     * Сериализация в JSON-payload для `order_tickets.guests[].price_snapshot`.
     */
    public function toArray(): array
    {
        return [
            'base_price' => $this->basePrice->amount(),
            'options_sum' => $this->optionsSum->amount(),
            'discount' => $this->discount->amount(),
            'total' => $this->total()->amount(),
        ];
    }

    /**
     * Десериализация из JSON-payload (`order_tickets.guests[].price_snapshot`).
     *
     * Strict-валидация: все 3 ключа обязательны. Молчаливый fallback к 0 запрещён —
     * это маскировало бы data corruption (например, силовое чтение из частично
     * мигрированной БД дало бы заказы с нулевой ценой).
     *
     * Поле `total` в payload игнорируется — пересчитывается через {@see self::total()}
     * (защита от рассинхрона payload).
     *
     * @throws InvalidArgumentException если отсутствует один из ключей base_price / options_sum / discount
     */
    public static function fromState(array $data): self
    {
        foreach (['base_price', 'options_sum', 'discount'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException(sprintf(
                    'MoneySnapshot::fromState() requires key "%s" — payload incomplete: %s',
                    $key,
                    json_encode($data, JSON_UNESCAPED_UNICODE),
                ));
            }
        }

        return new self(
            basePrice: new Money((int) $data['base_price']),
            optionsSum: new Money((int) $data['options_sum']),
            discount: new Money((int) $data['discount']),
        );
    }

    public function equals(MoneySnapshot $other): bool
    {
        return $this->basePrice->equals($other->basePrice)
            && $this->optionsSum->equals($other->optionsSum)
            && $this->discount->equals($other->discount);
    }
}
