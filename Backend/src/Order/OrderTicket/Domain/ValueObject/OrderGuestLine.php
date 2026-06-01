<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Domain\ValueObject;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Money;
use Shared\Domain\ValueObject\Uuid;

/**
 * OrderGuestLine — иммутабельный VO одной «строки» заказа: гость + тип билета + опции + цена.
 *
 * **Ядро BREAKING change v2.6.0** (см. `.claude/specs/order-format-architecture.md` §1).
 *
 * Раньше `OrderTicket` моделировал «один тип билета на весь заказ» —
 * `ticket_type_id`/`promo_code`/`price` были полями заказа. Теперь каждый гость
 * получает свой набор: оргвзнос + детский + парковка могут быть в одном заказе,
 * у каждого свой промокод и свои опции.
 *
 * Расчёт цены строки делает не сам VO, а **Application-сервис** `OrderPriceCalculator`
 * (Dependency Rule, Чистая архитектура гл. 22). VO принимает уже посчитанный
 * `MoneySnapshot` и **только хранит** его.
 *
 * **Денормализация флагов `isLiveTicket`/`isChildTicket`:** чтобы VO не лез в репозиторий
 * за `ticket_type.is_live_ticket`, флаг передаётся в конструктор (читается из ticket_type
 * на момент сборки строки в Application-сервисе). Так Domain остаётся самодостаточным
 * и не зависит от инфраструктуры.
 *
 * Для list-orders (`OrderTicket::createList`) `ticketTypeId = null` (списки —
 * билеты на локацию, без типа). См. `BUSINESS_RULES.md §12`.
 *
 * Источник: Чистая архитектура — Domain не знает про репозитории;
 * Совершенный код, гл. «Классы» — `with*()` методы возвращают новый объект (VO неизменяем).
 */
final class OrderGuestLine
{
    /**
     * Фиксированный UUID типа «Детский билет». Хранится также в `OrderTicket::CHILD_TICKET_TYPE_ID`
     * для обратной совместимости — после полной миграции на новый домен константа
     * там может быть удалена и единственным источником станет этот VO.
     */
    public const CHILD_TICKET_TYPE_ID = 'c3d4e5f6-a7b8-9012-cdef-345678901235';

    /**
     * @param OrderGuestOption[] $options
     */
    public function __construct(
        public readonly Uuid $id,
        public readonly string $value,
        public readonly ?string $email,
        public readonly ?int $number,
        public readonly Uuid $festivalId,
        public readonly ?Uuid $ticketTypeId,
        public readonly array $options,
        public readonly ?string $promoCode,
        public readonly MoneySnapshot $price,
        public readonly bool $isLiveTicket = false,
    ) {
    }

    /**
     * Итог по строке = total() из snapshot.
     * `OrderTicket::totalPrice()` агрегирует это по всем строкам.
     */
    public function total(): Money
    {
        return $this->price->total();
    }

    public function isChild(): bool
    {
        return $this->ticketTypeId !== null
            && $this->ticketTypeId->value() === self::CHILD_TICKET_TYPE_ID;
    }

    public function isLive(): bool
    {
        return $this->isLiveTicket;
    }

    /**
     * Регенерация ID строки. Используется в `toChangeTicket` при пересоздании билета
     * (чтобы старые QR-коды стали невалидны).
     */
    public function withRegeneratedId(): self
    {
        return new self(
            id: Uuid::random(),
            value: $this->value,
            email: $this->email,
            number: $this->number,
            festivalId: $this->festivalId,
            ticketTypeId: $this->ticketTypeId,
            options: $this->options,
            promoCode: $this->promoCode,
            price: $this->price,
            isLiveTicket: $this->isLiveTicket,
        );
    }

    /**
     * Сменить ФИО гостя (или его эквивалент — для парковки строку «номер/марка/водитель»).
     * Используется в `toChangeTicket`.
     */
    public function withValue(string $value): self
    {
        return new self(
            id: $this->id,
            value: $value,
            email: $this->email,
            number: $this->number,
            festivalId: $this->festivalId,
            ticketTypeId: $this->ticketTypeId,
            options: $this->options,
            promoCode: $this->promoCode,
            price: $this->price,
            isLiveTicket: $this->isLiveTicket,
        );
    }

    public function withEmail(?string $email): self
    {
        return new self(
            id: $this->id,
            value: $this->value,
            email: $email,
            number: $this->number,
            festivalId: $this->festivalId,
            ticketTypeId: $this->ticketTypeId,
            options: $this->options,
            promoCode: $this->promoCode,
            price: $this->price,
            isLiveTicket: $this->isLiveTicket,
        );
    }

    /**
     * Установить номер живого билета (заполняется в `toLiveIssued`).
     */
    public function withNumber(?int $number): self
    {
        return new self(
            id: $this->id,
            value: $this->value,
            email: $this->email,
            number: $number,
            festivalId: $this->festivalId,
            ticketTypeId: $this->ticketTypeId,
            options: $this->options,
            promoCode: $this->promoCode,
            price: $this->price,
            isLiveTicket: $this->isLiveTicket,
        );
    }

    /**
     * Сериализация в `order_tickets.guests[]` JSON-payload (один элемент массива).
     *
     * Формат payload — расширение существующего `GuestsDto::toArray()`:
     * - старые поля: `id`, `value`, `email`, `number`, `festival_id`
     * - новые поля: `ticket_type_id`, `options[]`, `promo_code`, `price_snapshot`, `is_live_ticket`
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->value(),
            'value' => $this->value,
            'email' => $this->email,
            'number' => $this->number,
            'festival_id' => $this->festivalId->value(),
            'ticket_type_id' => $this->ticketTypeId?->value(),
            'options' => array_map(
                static fn (OrderGuestOption $option): array => $option->toArray(),
                $this->options,
            ),
            'promo_code' => $this->promoCode,
            'price_snapshot' => $this->price->toArray(),
            'is_live_ticket' => $this->isLiveTicket,
        ];
    }

    /**
     * Десериализация одного элемента из JSON-payload `order_tickets.guests[]`.
     *
     * Strict-валидация обязательных полей: `id`, `value`, `festival_id`, `price_snapshot`.
     * Без этого нельзя различить data corruption и legacy-данные — а после миграции БД
     * v2.6.0 (см. спеку §3.3) у всех записей эти поля должны быть.
     *
     * Опциональные поля принимают разумные дефолты:
     * - `email` → null
     * - `number` → null
     * - `ticket_type_id` → null (валидно для заказов-списков)
     * - `options` → [] (валидно для заказа без опций)
     * - `promo_code` → null (валидно для заказа без промокода)
     * - `is_live_ticket` → false
     *
     * @throws InvalidArgumentException если отсутствует обязательное поле
     */
    public static function fromState(array $data): self
    {
        foreach (['id', 'value', 'festival_id', 'price_snapshot'] as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException(sprintf(
                    'OrderGuestLine::fromState() requires key "%s" — payload incomplete: %s',
                    $key,
                    json_encode($data, JSON_UNESCAPED_UNICODE),
                ));
            }
        }

        return new self(
            id: new Uuid($data['id']),
            value: $data['value'],
            email: $data['email'] ?? null,
            number: isset($data['number']) ? (int) $data['number'] : null,
            festivalId: new Uuid($data['festival_id']),
            ticketTypeId: isset($data['ticket_type_id']) && $data['ticket_type_id'] !== null
                ? new Uuid($data['ticket_type_id'])
                : null,
            options: array_map(
                static fn (array $raw): OrderGuestOption => OrderGuestOption::fromState($raw),
                $data['options'] ?? [],
            ),
            promoCode: $data['promo_code'] ?? null,
            price: MoneySnapshot::fromState($data['price_snapshot']),
            isLiveTicket: (bool) ($data['is_live_ticket'] ?? false),
        );
    }
}
