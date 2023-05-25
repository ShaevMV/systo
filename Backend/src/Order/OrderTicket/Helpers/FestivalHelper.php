<?php

namespace Tickets\Order\OrderTicket\Helpers;

class FestivalHelper
{
    public const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b2';
    public const UUID_SECOND_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b3';

    public static function isSpring($value): bool
    {
        return $value === self::UUID_SECOND_FESTIVAL;
    }
}
