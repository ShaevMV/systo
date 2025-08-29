<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ExternalPromocode;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;

final class InsertOrderIdInExternalPromocodeCommand implements Command
{
    public function __construct(
        protected Uuid $orderId
    )
    {}

    /**
     * @return Uuid
     */
    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }


}
