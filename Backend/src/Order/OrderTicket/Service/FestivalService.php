<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;

class FestivalService
{
    public function __construct(
       private FestivalRepositoryInterface $festivalRepository
    )
    {
    }

    public function getFestivalNameByTicketType(Uuid $ticketTypeId): string
    {
        return FestivalHelper::getNameFestival($this->festivalRepository->getFestivalByTicketTypeId($ticketTypeId));
    }
}
