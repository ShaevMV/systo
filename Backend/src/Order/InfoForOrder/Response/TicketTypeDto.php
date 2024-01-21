<?php

declare(strict_types=1);

namespace Tickets\Order\InfoForOrder\Response;

use Tickets\Order\InfoForOrder\DTO\FestivalDto;
use Tickets\Order\InfoForOrder\DTO\PriceDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
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
     * @param Uuid[] $festivalIdList
     * @param int $sort
     */
    public function __construct(
        protected Uuid   $id,
        protected string $name,
        protected float  $price,
        protected ?int   $groupLimit,
        protected array  $festivalIdList,
        protected array  $priceIdList,
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
        }, $data['festival'] ?? []);

        /** @var PriceDto[] $priceList */
        $priceList = array_map(function ($dataPrice) {
            return PriceDto::fromState($dataPrice);
        },$data['ticket_type_price'] ?? []);


        $correctPrice = end($priceList);
        return new self(
            new Uuid($data['id']),
            $data['name'],
            $correctPrice->getPrice(),
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

    public function getFestivalIdList(): array
    {
        return $this->festivalIdList;
    }
}
