<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Repositories;

use Tickets\PromoCode\Response\ExternalPromoCodeDto;
use Shared\Domain\ValueObject\Uuid;

interface ExternalPromoCodeInterface
{
    public function find(Uuid $ticketTypeId): ?ExternalPromoCodeDto;

    public function insertOrder(Uuid $ticketTypeId): bool;
}
