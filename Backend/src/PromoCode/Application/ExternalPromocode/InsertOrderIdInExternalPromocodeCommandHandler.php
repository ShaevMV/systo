<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\ExternalPromocode;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\PromoCode\Repositories\ExternalPromoCodeInterface;

final class InsertOrderIdInExternalPromocodeCommandHandler implements CommandHandler
{
    public function __construct(
        private ExternalPromoCodeInterface $promoCode
    )
    {
    }

    public function __invoke(InsertOrderIdInExternalPromocodeCommand $command)
    {
        $this->promoCode->insertOrder($command->getOrderId());
    }
}
