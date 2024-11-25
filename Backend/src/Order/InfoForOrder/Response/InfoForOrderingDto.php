<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

final class InfoForOrderingDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected ListTicketTypeDto $listTicketTypeDto,
        protected ListTypesOfPaymentDto $listTypesOfPaymentDto,
    ) {
    }

    /**
     * @return ListTicketTypeDto
     */
    public function getListTicketTypeDto(): ListTicketTypeDto
    {
        return $this->listTicketTypeDto;
    }

    public function toArray(): array
    {
        return [
            'ticketType' => $this->listTicketTypeDto->toArray()['ticketType'] ?? [],
            'typesOfPayment' => $this->listTypesOfPaymentDto->toArray()['typesOfPaymentDto'] ?? [],
        ];
    }
}
