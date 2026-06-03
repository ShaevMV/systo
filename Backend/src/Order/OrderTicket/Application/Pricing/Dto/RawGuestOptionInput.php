<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing\Dto;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;

/**
 * RawGuestOptionInput — одна сырая опция гостя из payload запроса.
 *
 * Формат payload: `{option_id: "uuid", qty: 2}` — `qty` хранится на входе,
 * а Domain VO {@see \Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestOption} кратность
 * не моделирует (см. {@see RawGuestInput} «Кратность опций»).
 *
 * `qty` обязательно ≥ 1. Пустые/нулевые/отрицательные значения отвергаются —
 * иначе атакующий мог бы «бесплатно» добавить опцию через `qty: 0`, что после раскрытия
 * в массив дало бы 0 элементов (опция вроде есть в payload, но не учтена).
 * Если опция не нужна — её не должно быть в массиве `options` вообще.
 */
final class RawGuestOptionInput
{
    /**
     * Защита от DoS: payload `{qty: 999999}` развернётся в 999999 объектов
     * `OrderGuestOption` в памяти при `expandOptions()` в Calculator. Реалистичный потолок:
     * 1 гость × 1 опция × **20 штук** — этого хватит для любого здравого сценария
     * (партия саженцев и пр.). Если бизнесу нужно больше — увеличим явно.
     */
    public const MAX_QTY = 20;

    public function __construct(
        public readonly Uuid $optionId,
        public readonly int $qty,
    ) {
        if ($this->qty < 1) {
            throw new InvalidArgumentException(sprintf(
                'RawGuestOptionInput::qty must be ≥ 1, got %d', $this->qty
            ));
        }

        if ($this->qty > self::MAX_QTY) {
            throw new InvalidArgumentException(sprintf(
                'RawGuestOptionInput::qty must be ≤ %d, got %d (защита от DoS — слишком много снапшотов опций)',
                self::MAX_QTY,
                $this->qty,
            ));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @throws InvalidArgumentException если отсутствует `option_id` или `qty` некорректен
     */
    public static function fromState(array $data): self
    {
        if (! isset($data['option_id']) || $data['option_id'] === '') {
            throw new InvalidArgumentException(sprintf(
                'RawGuestOptionInput::fromState() requires "option_id" — got: %s',
                json_encode($data, JSON_UNESCAPED_UNICODE)
            ));
        }

        $rawQty = $data['qty'] ?? 1;

        // Защита от тихого `(int) null = 0` / `(int) "abc" = 0` — это бы дало qty=0
        // после конструктора (там exception), но лучше понятнее ошибиться раньше.
        if (! is_int($rawQty) && ! (is_string($rawQty) && preg_match('/^\d+$/', $rawQty) === 1)) {
            throw new InvalidArgumentException(sprintf(
                'RawGuestOptionInput::fromState() field "qty" must be positive integer, got %s: %s',
                get_debug_type($rawQty),
                var_export($rawQty, true),
            ));
        }

        return new self(
            optionId: new Uuid((string) $data['option_id']),
            qty: (int) $rawQty,
        );
    }
}
