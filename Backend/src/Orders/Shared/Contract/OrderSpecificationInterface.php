<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Contract;

/**
 * Спецификация для проверки бизнес-правил заказа.
 *
 * Используется перед созданием заказа и при смене статуса.
 * Каждый тип заказа формирует свой набор спецификаций через CompositeOrderSpecification.
 *
 * Примеры конкретных спецификаций:
 * - PromoCodeSpecification    — проверяет корректность промокода и лимит использований
 * - GuestCountSpecification   — проверяет допустимое количество гостей для типа билета
 */
interface OrderSpecificationInterface
{
    /**
     * Проверяет выполнение условий спецификации.
     *
     * @param array $context Контекст проверки (dto, promo code, limits и т.д.)
     */
    public function isSatisfiedBy(array $context): bool;

    /**
     * Возвращает ошибки валидации после неуспешной проверки.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array;
}
