<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Util;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;

class TicketUtil
{
    /**
     * @param Uuid $uuid
     * @param array $rawGuests
     * @return GuestsDto|null
     */
    public static function findGuestByUuid(Uuid $uuid, array $rawGuests): ?GuestsDto
    {
        foreach ($rawGuests as $guest) {
            if($uuid->equals(new Uuid($guest['id']))) {
                return new GuestsDto(
                    $guest['value'],
                    $guest['email'],
                    new Uuid($guest['id']),
                    null
                );
            }
        }
    }
}
