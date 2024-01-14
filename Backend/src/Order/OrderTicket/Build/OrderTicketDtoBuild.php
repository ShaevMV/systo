<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Build;

use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class OrderTicketDtoBuild
{
    public static function build(
        array $data,

    ):array|OrderTicketDto
    {
        $data['festival_id'] = FestivalHelper::UUID_SECOND_FESTIVAL;

        $orderTicketDto = OrderTicketDto::fromState(
            $data,
            $userId,
            $priceDto,
        );
    }
}
