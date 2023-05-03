<?php

declare(strict_types=1);

namespace Baza\Tickets\ValueObject;

class Color
{
    public const COLOR_ELECTRON = 'white';
    public const COLOR_SPISOK = 'red';
    public const COLOR_LIVE = 'white';
    public const COLOR_FRIENDLY = 'white';

    private const COLORS = [
        self::COLOR_ELECTRON,
        self::COLOR_SPISOK,
        self::COLOR_LIVE,
        self::COLOR_FRIENDLY,
    ];

    public function __construct(
        private string $value
    )
    {
        if (!in_array($value, self::COLORS)) {
            throw new \InvalidArgumentException('Не верный цвет браслета ' . $value);
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

}
