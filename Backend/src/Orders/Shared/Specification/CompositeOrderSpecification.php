<?php

declare(strict_types=1);

namespace Tickets\Orders\Shared\Specification;

use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;

/**
 * Составная спецификация — объединяет несколько спецификаций через логику AND.
 *
 * Прерывается при первой неудачной проверке (fail-fast).
 * Каждый тип заказа формирует свой набор через конструктор.
 */
final class CompositeOrderSpecification implements OrderSpecificationInterface
{
    private array $errors = [];

    /** @param OrderSpecificationInterface[] $specifications */
    public function __construct(private readonly array $specifications) {}

    public function isSatisfiedBy(array $context): bool
    {
        $this->errors = [];

        foreach ($this->specifications as $specification) {
            if (!$specification->isSatisfiedBy($context)) {
                $this->errors = array_merge($this->errors, $specification->getErrors());
                return false;
            }
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
