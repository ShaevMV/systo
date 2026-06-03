<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\Pricing\Dto;

use InvalidArgumentException;
use Shared\Domain\ValueObject\Uuid;

/**
 * RawGuestInput — структурированный вход в {@see OrderPriceCalculator}.
 *
 * Что: один «сырой» гость до расчёта цены — то, что приходит от фронта в payload
 * `POST /api/v1/order/create` (см. `.claude/specs/order-format-architecture.md` §1.1).
 *
 * Зачем DTO, а не `array`: на границе Application слоя `array` ломается без предупреждения
 * (опечатки в ключах, неверные типы, пропущенные поля). Конструктор + фабрика {@see fromState}
 * выполняют strict-валидацию ровно один раз — дальше Calculator работает с уверенным типом.
 *
 * **Кратность опций:** payload приходит как `options: [{option_id: "...", qty: 2}]`
 * (зафиксировано на встрече 2026-05-30 — см. `.claude/docs/BOARD.md`).
 * Calculator разворачивает `qty` в N снимков {@see \Tickets\Order\OrderTicket\Domain\ValueObject\OrderGuestOption}
 * — Domain VO кратность не моделирует.
 *
 * Источник: Чистая архитектура (Р. Мартин), гл. «Границы» — валидация на границе слоя;
 * Совершенный код, гл. «Защитное программирование» — отвергать неверный вход явно.
 */
final class RawGuestInput
{
    /**
     * **Email обязателен** для каждой строки (включая водителя парковочного билета):
     * на этот email уходит ссылка-приглашение анкеты гостя (`ProcessGuestNotificationQuestionnaire`),
     * без email гость остаётся «потерянным» — ни анкеты, ни Telegram-уведомления.
     *
     * Решение зафиксировано пользователем 2026-06-03 для нового формата заказа.
     *
     * @param  RawGuestOptionInput[]  $options
     */
    public function __construct(
        public readonly string $value,
        public readonly string $email,
        public readonly Uuid $ticketTypeId,
        public readonly array $options,
        public readonly ?string $promoCode,
    ) {
        foreach ($this->options as $option) {
            if (! $option instanceof RawGuestOptionInput) {
                throw new InvalidArgumentException(
                    'RawGuestInput::options must contain only RawGuestOptionInput instances'
                );
            }
        }
    }

    /**
     * Десериализация из payload запроса (`guests[]` элемент).
     *
     * **Обязательные поля:** `value`, `email`, `ticket_type_id`.
     * - `email` обязателен и для парковки (адрес водителя — на него уходит анкета)
     *
     * Опциональные:
     * - `options` → [] (заказ без доп. опций)
     * - `promo_code` → null
     *
     * Нормализуем `promo_code` — `trim()` + пустая строка → null (как раньше в `PriceService`).
     *
     * @param  array<string, mixed>  $data
     * @throws InvalidArgumentException при отсутствии обязательных полей или неверных типах
     */
    public static function fromState(array $data): self
    {
        foreach (['value', 'email', 'ticket_type_id'] as $key) {
            if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
                throw new InvalidArgumentException(sprintf(
                    'RawGuestInput::fromState() requires non-empty "%s" — got: %s',
                    $key,
                    json_encode($data, JSON_UNESCAPED_UNICODE)
                ));
            }
        }

        // Email — корректный формат. Без этой проверки в БД попадёт «abc» и анкета
        // уйдёт в /dev/null. Защищаемся явно: ValidateRequest на контроллере мог быть
        // пропущен (например, для legacy-эндпоинта), а Calculator — на границе слоя
        // должен ловить мусор сам.
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(sprintf(
                'RawGuestInput::fromState() field "email" must be a valid email, got: %s',
                var_export($data['email'], true),
            ));
        }

        $rawOptions = $data['options'] ?? [];
        if (! is_array($rawOptions)) {
            throw new InvalidArgumentException(sprintf(
                'RawGuestInput::fromState() field "options" must be array, got %s',
                get_debug_type($rawOptions)
            ));
        }

        // Каждый элемент `options[]` обязан быть массивом (вложенный payload опции).
        // Без этой проверки `array_map(fn (array $raw) => ...)` бросит непредсказуемый
        // `TypeError` (HTTP 500) при `options: ["x"]` / `options: [null]` — а граница
        // слоя должна возвращать домен-понятный `InvalidArgumentException`.
        $options = [];
        foreach (array_values($rawOptions) as $index => $raw) {
            if (! is_array($raw)) {
                throw new InvalidArgumentException(sprintf(
                    'RawGuestInput::fromState() options[%d] must be array, got %s: %s',
                    $index,
                    get_debug_type($raw),
                    var_export($raw, true),
                ));
            }
            $options[] = RawGuestOptionInput::fromState($raw);
        }

        $promoCode = $data['promo_code'] ?? null;
        if (is_string($promoCode)) {
            $promoCode = trim($promoCode);
            if ($promoCode === '') {
                $promoCode = null;
            }
        }

        return new self(
            value: (string) $data['value'],
            email: (string) $data['email'],
            ticketTypeId: new Uuid((string) $data['ticket_type_id']),
            options: $options,
            promoCode: $promoCode,
        );
    }
}
