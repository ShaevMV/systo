<?php

namespace Tickets\PromoCode\Application\CreatePromoCode;

use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\Bus\Command\CommandHandler;

class CreateOrUpdatePromoCodeCommandHandler implements CommandHandler
{
    public function __construct(
        private PromoCodeInterface $promoCode
    )
    {
    }

    public function __invoke(CreateOrUpdatePromoCodeCommand $command)
    {
        $this->promoCode->createOrUpdate(new PromoCodeDto(
            new LimitPromoCodeDto(0,$command->getLimit()),
            "",
            $command->getId(),
            $command->getName(),
            $command->getDiscount(),
            $command->isActive(),
            $command->isPercent()
        ));
    }
}
