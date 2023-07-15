<?php

declare(strict_types=1);

namespace Shared\Domain\Criteria;

use Shared\Domain\ValueObject\Enum;
use InvalidArgumentException;

/**
 * @method static FilterOperator gt()
 * @method static FilterOperator lt()
 */
final class FilterOperator extends Enum
{
    public const EQUAL        = '=';
    public const LIKE         = 'LIKE';
    public const NOT_EQUAL    = '!=';
    public const GT           = '>';
    public const LT           = '<';
    public const CONTAINS     = 'CONTAINS';
    public const NOT_CONTAINS = 'NOT_CONTAINS';
    private static array $containing = [self::CONTAINS, self::NOT_CONTAINS];

    public function isContaining(): bool
    {
        return in_array($this->value(), self::$containing, true);
    }

    protected function throwExceptionForInvalidValue($value): void
    {
        throw new InvalidArgumentException(sprintf('The filter <%s> is invalid', $value));
    }

    public function like(string $value): string
    {
        return str_replace("{value}", $value, self::LIKE);
    }
}
