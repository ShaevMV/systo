<?php

declare(strict_types=1);

namespace Shared\Domain\Criteria;

final class Filter
{
    public function __construct(
        private FilterField $field,
        private FilterOperator $operator,
        private FilterValue|FilterBoolValue $value
    ) {
    }

    public static function fromValues(array $values): self
    {
        return new self(
            new FilterField($values['field']),
            new FilterOperator($values['operator']),
            is_bool($values['value']) ? new FilterBoolValue($values['value']) : new FilterValue($values['value'])
        );
    }

    public function field(): FilterField
    {
        return $this->field;
    }

    public function operator(): FilterOperator
    {
        return $this->operator;
    }

    public function value(): FilterValue|FilterBoolValue
    {
        return $this->value;
    }

    public function serialize(): string
    {
        return sprintf('%s.%s.%s', $this->field->value(), $this->operator->value(), $this->value->value());
    }
}
