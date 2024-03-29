<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class PromoCodeDto extends AbstractionEntity implements Response
{
    private const MASSAGE_IN_SUCCESS = 'Ваш промо код принят, ваша скидка составит {getDiscountByPromoCode} ₽ за каждый билет';
    private const MASSAGE_IN_ERROR = 'Промокод не принят!';

    private const MASSAGE_IN_ERROR_FOR_TYPE = 'Данные промокод не подходит к этому типу билета';


    public function __construct(
        protected string $massage = self::MASSAGE_IN_ERROR,
        protected ?Uuid $id = null,
        protected ?string $name = null,
        protected float $discount = 0.00,
        protected bool $isSuccess = false,
    ) {
    }

    public static function fromState(array $data): self
    {
        $massage = str_replace('{getDiscountByPromoCode}', (string)$data['discount'], self::MASSAGE_IN_SUCCESS);

        return new self(
            $massage,
            new Uuid($data['id']),
            $data['name'],
            $data['discount'],
            true
        );
    }

    public static function fromGroupTicket(): self
    {
        return new self(
            self::MASSAGE_IN_ERROR_FOR_TYPE
        );
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }
}
