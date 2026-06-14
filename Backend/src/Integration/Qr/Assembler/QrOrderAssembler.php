<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Assembler;

use InvalidArgumentException;
use Shared\Integration\Rabbit\EventEnvelope;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;

/**
 * Антикоррупционный слой между контрактом витрины qr.spaceofjoy.ru и доменом org.
 *
 * Превращает payload события `order.created` (CONTRACT_RFC_v0.md §5) в типизированный
 * {@see AssembledQrOrder}: валидирует обязательные поля, мапит type_order, идентичность
 * (friendly/curator/location), опции ({@see RawGuestOptionInput}) и ДЕКЛАРИРОВАННУЮ qr цену (Р2).
 *
 * Без БД и без расчётов — чистое преобразование. Любой невалидный контракт → перманентный
 * {@see QrOrderRejectedException} (консьюмер трактует как reject без requeue, не зацикливает).
 * Существование UUID (festival/option/identity) и применение цены — шаг создания заказа (Ф3).
 *
 * Источник: Чистая архитектура (Р. Мартин) — «Границы»: антикоррупционный слой защищает
 * домен от чужой модели данных.
 */
final class QrOrderAssembler
{
    public function assemble(EventEnvelope $envelope): AssembledQrOrder
    {
        $payload = $envelope->payload;
        $order = $this->requireArray($payload, 'order');
        $rawGuests = $this->requireArray($payload, 'guests');

        if ($rawGuests === []) {
            throw new QrOrderRejectedException('guests[] пуст — заказ без гостей невозможен');
        }

        $type = $this->reject(static fn () => new QrOrderType((string) ($order['type_order'] ?? '')));

        $user = is_array($order['user'] ?? null) ? $order['user'] : [];
        $price = is_array($order['price'] ?? null) ? $order['price'] : [];

        return new AssembledQrOrder(
            type: $type,
            qrOrderId: $this->requireString($order, 'qr_order_id'),
            festivalId: $this->requireString($order, 'festival_id'),
            recipientEmail: $this->requireEmail($order, 'email'),
            recipientName: $this->optString($user, 'name'),
            recipientCity: $this->optString($user, 'city'),
            recipientPhone: $this->optString($user, 'phone'),
            typesOfPaymentId: $this->resolvePaymentId($order, $type),
            friendlyId: $this->resolveFriendlyId($order, $type),
            curatorId: $this->resolveCuratorId($order, $type),
            locationId: $this->resolveLocationId($order, $type),
            comment: $this->optString($order, 'comment'),
            declaredPrice: $type->isList() ? 0 : $this->toInt($price['price'] ?? 0, 'order.price.price'),
            declaredDiscount: $type->isList() ? 0 : $this->toInt($price['discount'] ?? 0, 'order.price.discount'),
            declaredTotal: $type->isList() ? 0 : $this->toInt($price['total'] ?? 0, 'order.price.total'),
            guests: array_map(
                fn (mixed $guest): AssembledQrGuest => $this->assembleGuest($guest, $type),
                array_values($rawGuests),
            ),
        );
    }

    private function assembleGuest(mixed $guest, QrOrderType $type): AssembledQrGuest
    {
        if (! is_array($guest)) {
            throw new QrOrderRejectedException('Элемент guests[] должен быть объектом');
        }

        $promoCode = $this->optString($guest, 'promocode');
        $liveNumber = $this->optString($guest, 'number');

        // Заказ-список: без типа билета, опций и цены (списки бесплатны, CONTRACT_RFC_v0.md §6.1).
        if ($type->isList()) {
            return new AssembledQrGuest(
                value: $this->requireString($guest, 'name'),
                email: $this->requireEmail($guest, 'email'),
                ticketTypeId: null,
                options: [],
                promoCode: $promoCode,
                liveNumber: $liveNumber,
                declaredBasePrice: 0,
                declaredOptionsSum: 0,
                declaredDiscount: 0,
                declaredTotal: 0,
            );
        }

        $ticketType = $this->requireArray($guest, 'ticket_type');
        $price = is_array($guest['price'] ?? null) ? $guest['price'] : [];

        return new AssembledQrGuest(
            value: $this->requireString($guest, 'name'),
            email: $this->requireEmail($guest, 'email'),
            ticketTypeId: $this->requireString($ticketType, 'id'),
            options: $this->assembleOptions($ticketType['options'] ?? []),
            promoCode: $promoCode,
            liveNumber: $liveNumber,
            declaredBasePrice: $this->toInt($price['base_price'] ?? 0, 'guests[].price.base_price'),
            declaredOptionsSum: $this->toInt($price['options_sum'] ?? 0, 'guests[].price.options_sum'),
            declaredDiscount: $this->toInt($price['discount'] ?? 0, 'guests[].price.discount'),
            declaredTotal: $this->toInt($price['total'] ?? 0, 'guests[].price.total'),
        );
    }

    /**
     * @param mixed $rawOptions
     * @return RawGuestOptionInput[]
     */
    private function assembleOptions(mixed $rawOptions): array
    {
        if (! is_array($rawOptions)) {
            throw new QrOrderRejectedException('ticket_type.options должен быть массивом');
        }

        return array_map(
            fn (mixed $raw): RawGuestOptionInput => $this->reject(static function () use ($raw): RawGuestOptionInput {
                if (! is_array($raw)) {
                    throw new InvalidArgumentException('опция должна быть объектом {option_id, qty}');
                }

                return RawGuestOptionInput::fromState($raw);
            }),
            array_values($rawOptions),
        );
    }

    private function resolvePaymentId(array $order, QrOrderType $type): ?string
    {
        if ($type->isList()) {
            return null; // списки без оплаты
        }
        $payment = is_array($order['types_of_payment'] ?? null) ? $order['types_of_payment'] : [];

        return $this->requireString($payment, 'id', 'order.types_of_payment.id');
    }

    private function resolveFriendlyId(array $order, QrOrderType $type): ?string
    {
        if (! $type->isFriendly()) {
            return null;
        }
        // org хранит в friendly_id UUID пушера: friendly.id, иначе pusher.id (CONTRACT_RFC_v0.md §6.2).
        $friendly = is_array($order['friendly'] ?? null) ? $order['friendly'] : [];
        $pusher = is_array($order['pusher'] ?? null) ? $order['pusher'] : [];
        $id = $this->optString($friendly, 'id') ?? $this->optString($pusher, 'id');

        if ($id === null) {
            throw new QrOrderRejectedException('type_order=friendly требует friendly.id или pusher.id');
        }

        return $id;
    }

    private function resolveCuratorId(array $order, QrOrderType $type): ?string
    {
        if (! $type->isList()) {
            return null;
        }
        $curator = is_array($order['curator'] ?? null) ? $order['curator'] : [];

        return $this->requireString($curator, 'id', 'order.curator.id');
    }

    private function resolveLocationId(array $order, QrOrderType $type): ?string
    {
        if (! $type->isList()) {
            return null;
        }
        $location = is_array($order['location'] ?? null) ? $order['location'] : [];

        return $this->requireString($location, 'id', 'order.location.id');
    }

    /** @param array<string, mixed> $data */
    private function requireArray(array $data, string $key): array
    {
        if (! isset($data[$key]) || ! is_array($data[$key])) {
            throw new QrOrderRejectedException(sprintf('Поле "%s" обязательно и должно быть объектом', $key));
        }

        return $data[$key];
    }

    /** @param array<string, mixed> $data */
    private function requireString(array $data, string $key, ?string $label = null): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            throw new QrOrderRejectedException(sprintf('Поле "%s" обязательно и не может быть пустым', $label ?? $key));
        }

        return trim($value);
    }

    /** @param array<string, mixed> $data */
    private function requireEmail(array $data, string $key): string
    {
        $value = $this->requireString($data, $key);
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new QrOrderRejectedException(sprintf('Поле "%s" должно быть корректным email, получено: %s', $key, $value));
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    private function optString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function toInt(mixed $value, string $label): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }
        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        throw new QrOrderRejectedException(sprintf('Поле "%s" должно быть целым числом рублей, получено: %s', $label, var_export($value, true)));
    }

    /**
     * Выполнить замыкание, превратив доменную InvalidArgumentException (из RawGuestOptionInput,
     * QrOrderType и т.п.) в перманентный QrOrderRejectedException — чтобы консьюмер сделал
     * reject без requeue, а не зациклил «ядовитое» сообщение.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function reject(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (QrOrderRejectedException $e) {
            throw $e;
        } catch (InvalidArgumentException $e) {
            throw new QrOrderRejectedException($e->getMessage(), 0, $e);
        }
    }
}
