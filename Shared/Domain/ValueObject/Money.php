<?php

declare(strict_types=1);

namespace Shared\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Money — иммутабельный Value Object для денежной суммы.
 *
 * Хранит сумму в **целых рублях** (`int`). Копейки в проекте не используются —
 * все цены билетов и опций — кратны рублю.
 *
 * Источник: Чистая архитектура (Р. Мартин), гл. «Сущности»;
 * Совершенный код, гл. «Классы» — Money — классический пример VO.
 */
final class Money
{
    public function __construct(
        private readonly int $amount,
        private readonly string $currency = 'RUB',
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException(
                sprintf('Money amount cannot be negative, got %d', $amount)
            );
        }
    }

    public static function zero(string $currency = 'RUB'): self
    {
        return new self(0, $currency);
    }

    /**
     * Из float — округление до целых рублей (банкирское, half-even).
     *
     * Используется на границе с legacy-кодом, где цены — float (`ticket_type_price.price`).
     *
     * Защищает от тихих ошибок на граничных значениях:
     * - `NaN` / `INF` — `(int) NaN` в PHP молча даёт `0` и испортит сумму
     * - значения вне диапазона `int` после округления — кастинг к int обрезает
     * - отрицательные значения отсекаются конструктором
     */
    public static function fromFloat(float $value, string $currency = 'RUB'): self
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException(
                sprintf('Money cannot be created from non-finite value: %s', var_export($value, true))
            );
        }

        $rounded = round($value, 0, PHP_ROUND_HALF_EVEN);

        if ($rounded > PHP_INT_MAX || $rounded < PHP_INT_MIN) {
            throw new InvalidArgumentException(
                sprintf('Money amount out of int range after rounding: %s', var_export($rounded, true))
            );
        }

        return new self((int) $rounded, $currency);
    }

    /**
     * Строгая фабрика для десериализации из payload (JSON/array).
     *
     * Принимает только `int` или строку-целое (`"4200"`, `"-100"`). Всё остальное
     * (`null`, `""`, `"abc"`, `"12.5"`, `false`, объекты) — бросает {@see InvalidArgumentException}.
     *
     * Зачем: на входной границе `(int) $value` молча превращает `null`/`""`/`"abc"`
     * в `0` — это маскирует data corruption и в случае с ценой опции даёт
     * **бесплатные опции** через корявый payload (атакующий вектор).
     * Поэтому `fromState()` методов VO должны использовать эту фабрику.
     *
     * @param mixed $value  значение из payload — что угодно
     * @param string $field имя поля для понятного сообщения об ошибке
     */
    public static function fromInteger(mixed $value, string $field = 'value', string $currency = 'RUB'): self
    {
        if (is_int($value)) {
            return new self($value, $currency);
        }

        if (is_string($value) && $value !== '' && preg_match('/^-?\d+$/', $value) === 1) {
            $intValue = (int) $value;
            // Проверяем что строка влезает в int (для очень больших чисел приведение к int обрезает)
            if ((string) $intValue !== $value) {
                throw new InvalidArgumentException(sprintf(
                    'Money::fromInteger() field "%s": value out of int range: %s',
                    $field,
                    $value
                ));
            }

            return new self($intValue, $currency);
        }

        throw new InvalidArgumentException(sprintf(
            'Money::fromInteger() field "%s" requires int or numeric string, got %s: %s',
            $field,
            get_debug_type($value),
            var_export($value, true)
        ));
    }

    public function amount(): int
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function asFloat(): float
    {
        return (float) $this->amount;
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);

        // Защита от int overflow: при выходе за PHP_INT_MAX PHP молча конвертирует
        // int → float, и конструктор Money (strict_types) кинет TypeError.
        // Бросаем понятный доменный InvalidArgumentException заранее.
        if (PHP_INT_MAX - $this->amount < $other->amount) {
            throw new InvalidArgumentException(sprintf(
                'Money overflow in add(): %d + %d exceeds PHP_INT_MAX',
                $this->amount,
                $other->amount
            ));
        }

        return new self($this->amount + $other->amount, $this->currency);
    }

    /**
     * Вычитание с защитой от ухода в отрицательную сумму.
     * Если результат < 0, возвращает `Money::zero()` — для денежных операций отрицательная сумма недопустима.
     */
    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        $result = $this->amount - $other->amount;

        return new self(max(0, $result), $this->currency);
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException(
                sprintf('Money multiplier cannot be negative, got %d', $factor)
            );
        }

        // Защита от int overflow: при выходе за PHP_INT_MAX PHP конвертирует
        // результат в float, и конструктор (strict_types) кинет TypeError.
        // intdiv() избегает float-арифметики, оставаясь в int-домене.
        if ($factor !== 0 && intdiv(PHP_INT_MAX, $factor) < $this->amount) {
            throw new InvalidArgumentException(sprintf(
                'Money overflow in multiply(): %d * %d exceeds PHP_INT_MAX',
                $this->amount,
                $factor
            ));
        }

        return new self($this->amount * $factor, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Cannot operate on different currencies: %s vs %s',
                $this->currency,
                $other->currency
            ));
        }
    }
}
