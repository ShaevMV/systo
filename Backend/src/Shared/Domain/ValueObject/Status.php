<?php

declare(strict_types=1);

namespace Tickets\Shared\Domain\ValueObject;

use InvalidArgumentException;

final class Status
{
    public const NEW = 'new';
    public const PAID = 'paid';
    public const CANCEL = 'cancel';
    public const DIFFICULTIES_AROSE = 'Difficulties arose';


    private const STATUS_LIST = [
        self::NEW => 'Новый',
        self::CANCEL => 'Отменён',
        self::PAID => 'Оплаченный',
        self::DIFFICULTIES_AROSE => 'Возникли трудности',
    ];

    public function __construct(
        private string $name
    ) {
        $this->isCorrect($this->name);
    }

    private function isCorrect(string $name): void
    {
        if (!array_key_exists($name, self::STATUS_LIST)) {
            throw new InvalidArgumentException(sprintf('<%s> does not allow the value <%s>.', self::class, $name));
        }
    }
}
