<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;

final class InfoForOrderingDto extends AbstractionEntity implements Response
{
    /**
     * @param  TicketTypeDto[]  $ticketType
     * @param  TypesOfPaymentDto[]  $typesOfPayment
     */
    public function __construct(
        protected array $ticketType,
        protected array $typesOfPayment,
    ) {
    }

    public static function fromState(array $data): self
    {
        $ticketType = [];
        foreach ($data['ticketType'] as $datum) {
            $ticketType[] = TicketTypeDto::fromState($datum);
        }

        $typesOfPayment = [];
        foreach ($data['typesOfPayment'] as $datum) {
            $typesOfPayment[] = TypesOfPaymentDto::fromState($datum);
        }

        return new self(
            $ticketType,
            $typesOfPayment
        );
    }
}
