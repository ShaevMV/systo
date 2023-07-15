<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Application\SearchPromoCode;

use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\Bus\Query\QueryHandler;

final class PromoCodeQueryHandler implements QueryHandler
{
    public function __construct(
        private PromoCodeInterface $promoCode
    ) {
    }

    public function __invoke(PromoCodeQuery $query): PromoCodeDto
    {
        $result =$this->promoCode->find($query->getName()) ?? new PromoCodeDto(new LimitPromoCodeDto());
        if(!$result->isCorrectForLimit() || !$result->isSuccess()) {
            return new PromoCodeDto(new LimitPromoCodeDto());
        }

        return $result;
    }
}
