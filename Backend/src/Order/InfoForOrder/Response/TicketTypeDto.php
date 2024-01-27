<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Order\InfoForOrder\DTO\FestivalDto;
use Tickets\Order\InfoForOrder\DTO\PriceDto;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class TicketTypeDto extends AbstractionEntity implements Response
{
    /**
     * @param Uuid $id
     * @param string $name
     * @param float $price
     * @param int|null $groupLimit
     * @param FestivalDto[] $festivalList
     * @param PriceDto[] $priceList
     * @param int $sort
     */
    public function __construct(
        protected Uuid   $id,
        protected string $name,
        protected float  $price,
        protected ?int   $groupLimit,
        protected array  $festivalList,
        protected array  $priceList,
        protected int    $sort = 0,
    )
    {
    }


    public static function fromState(array $data): self
    {
        $groupLimit = isset($data['groupLimit']) && !empty($data['groupLimit']) ?
            (int)$data['groupLimit'] :
            null;


        $festivalIdList = array_map(function (array $dataFestival) {
            return FestivalDto::fromState($dataFestival);
        }, $data['festivals'] ?? []);

        /** @var PriceDto[] $priceList */
        $priceList = array_map(function ($dataPrice) {
            return PriceDto::fromState($dataPrice);
        }, $data['ticket_type_price'] ?? []);


        $correctPrice = count($priceList) > 0 ? end($priceList)->getPrice() : $data['price'];
        return new self(
            new Uuid($data['id']),
            $data['name'],
            $correctPrice,
            $groupLimit,
            $festivalIdList,
            $priceList,
            $data['sort'],
        );
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getGroupLimit(): ?int
    {
        return $this->groupLimit;
    }

    public function getFestivalListId(): array
    {
        return array_map(fn(FestivalDto $festivalDto) => $festivalDto->getId(), $this->festivalList);
    }

    public function getPriceList(): array
    {
        return $this->priceList;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }
}
