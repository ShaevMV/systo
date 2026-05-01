<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Specification;

use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;

/**
 * Спецификация: количество гостей в заказе соответствует ограничениям типа билета.
 *
 * Групповые билеты имеют groupLimit — максимальное количество мест.
 * Обычные билеты — минимум 1 гость.
 *
 * Контекст (array $context):
 * - 'guestCount'  => int        количество гостей в заказе
 * - 'groupLimit'  => int|null   максимальный лимит (null = без ограничения)
 * - 'minGuests'   => int        минимальное количество гостей (по умолчанию 1)
 */
final class GuestCountSpecification implements OrderSpecificationInterface
{
    private array $errors = [];

    public function isSatisfiedBy(array $context): bool
    {
        $this->errors = [];

        $guestCount = (int)($context['guestCount'] ?? 0);
        $groupLimit = $context['groupLimit'] ?? null;
        $minGuests  = (int)($context['minGuests'] ?? 1);

        if ($guestCount < $minGuests) {
            $this->errors['guests'] = [
                sprintf('Минимальное количество гостей: %d', $minGuests)
            ];
            return false;
        }

        if ($groupLimit !== null && $guestCount > $groupLimit) {
            $this->errors['guests'] = [
                sprintf('Превышен лимит мест: максимум %d', $groupLimit)
            ];
            return false;
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
