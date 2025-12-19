<?php

namespace Tickets\Order\OrderTicket\Helpers;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;

class FestivalHelper
{
    public const UUID_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    public const UUID_SECOND_FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b5';

    /**
     * @param FestivalDto[] $festivalList
     */
    public static function getNameFestival(array $festivalList = []): string
    {
        $nameFestival = [];
        foreach ($festivalList as $festivalDto) {
            if(!$festivalDto->getId()->equals(new Uuid(self::UUID_FESTIVAL))) {
                continue;
            }
            $nameFestival[] = $festivalDto->getName() . ' ' . $festivalDto->getYear();
        }


        return trim(implode(' и на ', $nameFestival));
    }
}
