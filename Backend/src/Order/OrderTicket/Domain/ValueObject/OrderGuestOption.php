<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Uuid;

/**
 * OrderGuestOption — снимок выбранной опции конкретного гостя (`OrderGuestLine`) на момент покупки.
 *
 * Опции — это доп. товары к билету: «Саженец», «Печатный билет», «Парковка» и т.п.
 * См. модуль `Backend/src/Option/` (введён в PR #61, v2.6.0).
 *
 * **Зачем снимок** (name + price):
 * - админ может переименовать опцию или поменять её цену после покупки — но у уже
 *   купленного билета должна быть зафиксирована **та** цена и **то** имя, которые
 *   были на момент оплаты. Это требование аудита и защиты пользователя.
 *
 * Кратность опций — НЕ в этом VO. По решению встречи 2026-05-30 (см.
 * `.claude/meetings/2026-05-30/RESULTS.md`) кратность реализуется через
 * **повторение** строки в `OrderGuestLine::$options[]` — два саженца — это два
 * элемента массива (а не один с qty=2). Это упрощает агрегацию и расчёт.
 *
 * См. `.claude/specs/order-format-architecture.md` §1.2, §2.3.
 *
 * Источник: Чистая архитектура (Р. Мартин), гл. «Сущности» — иммутабельный VO,
 * захватывает state в момент создания.
 */
final class OrderGuestOption
{
    public function __construct(
        public readonly Uuid $optionId,
        public readonly string $nameSnapshot,
        public readonly Money $priceSnapshot,
    ) {
    }

    /**
     * Сериализация в JSON-payload для `order_tickets.guests[].options[]`.
     */
    public function toArray(): array
    {
        return [
            'option_id' => $this->optionId->value(),
            'name' => $this->nameSnapshot,
            'price' => $this->priceSnapshot->amount(),
        ];
    }

    /**
     * Десериализация одной опции из JSON-payload.
     *
     * Strict-валидация: все 3 ключа обязательны. Особенно критично для `price` —
     * молчаливый fallback к 0 даёт **бесплатные опции** через корявый payload
     * (атакующий вектор / тихая порча данных).
     *
     * @throws InvalidArgumentException если отсутствует option_id / name / price
     */
    public static function fromState(array $data): self
    {
        foreach (['option_id', 'name', 'price'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException(sprintf(
                    'OrderGuestOption::fromState() requires key "%s" — payload incomplete: %s',
                    $key,
                    json_encode($data, JSON_UNESCAPED_UNICODE),
                ));
            }
        }

        return new self(
            optionId: new Uuid($data['option_id']),
            nameSnapshot: $data['name'],
            priceSnapshot: new Money((int) $data['price']),
        );
    }

    public function equals(OrderGuestOption $other): bool
    {
        return $this->optionId->equals($other->optionId)
            && $this->nameSnapshot === $other->nameSnapshot
            && $this->priceSnapshot->equals($other->priceSnapshot);
    }
}
