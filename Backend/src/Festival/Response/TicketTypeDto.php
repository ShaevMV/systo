<?php

declare(strict_types=1);

namespace Tickets\Festival\Response;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Festival\DTO\FestivalDto;
use Tickets\Festival\DTO\PriceDto;

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
     * @param bool $isLiveTicket
     * @param bool $isParking
     * @param string|null $description
     * @param Uuid|null $questionnaireTypeId
     */
    public function __construct(
        protected Uuid   $id,
        protected string $name,
        protected float  $price,
        protected ?int   $groupLimit,
        protected array  $festivalList,
        protected array  $priceList,
        protected int    $sort = 0,
        protected bool   $isLiveTicket = false,
        protected bool   $isParking = false,
        protected ?string $description = null,
        protected ?Uuid  $questionnaireTypeId = null,
    )
    {
    }


    public static function fromState(array $data, $isAllPrice = false): self
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

        if (!$isAllPrice) {
            $correctPrice = count($priceList) > 0 ? end($priceList)->getPrice() : $data['price'];
        } else {
            $correctPrice = $data['price'];
        }

        return new self(
            new Uuid($data['id']),
            $data['name'],
            $correctPrice,
            $groupLimit,
            $festivalIdList,
            $priceList,
            $data['sort'],
            (bool)$data['is_live_ticket'],
            (bool)($data['is_parking'] ?? false),
            $data['description'] ?? null,
            empty($data['questionnaire_type_id']) ? null : new Uuid($data['questionnaire_type_id']),
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

    public function isLiveTicket(): bool
    {
        return $this->isLiveTicket;
    }

    public function isParking(): bool
    {
        return $this->isParking;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getQuestionnaireTypeId(): ?Uuid
    {
        return $this->questionnaireTypeId;
    }
}
