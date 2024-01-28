<?php

namespace Tickets\Order\OrderTicket\Helpers;

use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;

class FestivalHelper
{
    public const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b4';
    public const UUID_SECOND_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b5';

    public const FESTIVAL_DEFAULT_NAME = 'Систо ';

    public static function isSpring($value): bool
    {
        return $value === self::UUID_SECOND_FESTIVAL;
    }

    /**
     * @param FestivalDto[] $festivalList
     */
    public static function getNameFestival(array $festivalList = []): string
    {
        $nameFestival = [];
        $year = date('Y');
        foreach ($festivalList as $festivalDto) {
            $nameFestival[] = $festivalDto->getName();
        }

        $bracketClose = '';
        $bracketOpen = '';
        if (count($nameFestival) >= 1) {
            $bracketOpen = '(';
            $bracketClose = ')';
        }

        return trim(self::FESTIVAL_DEFAULT_NAME . $year . ' ' . $bracketOpen . implode(' и ', $nameFestival) . $bracketClose);
    }
}
