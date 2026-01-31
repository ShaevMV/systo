<?php

declare(strict_types=1);

namespace Tickets\PromoCode\Response;

use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

final class PromoCodeDto extends AbstractionEntity implements Response
{
    private const message_IN_SUCCESS = 'Ваш промо код принят, ваша скидка составит {getDiscountByPromoCode} ₽ за каждый билет';
    private const message_IN_ERROR = 'Промокод не принят!';

    private const message_IN_ERROR_FOR_TYPE = 'Данные промокод не подходит к этому типу билета';

    public function __construct(
        protected LimitPromoCodeDto $limit,
        protected string $message = self::message_IN_ERROR,
        protected ?Uuid $id = null,
        protected ?string $name = null,
        protected float $discount = 0.00,
        protected bool $isSuccess = false,
        protected bool $isPercent = false,
        protected ?Uuid $ticket_type_id = null,
        protected ?string $ticket_type_name = null,
        protected ?string $festival = null,
        protected ?Uuid $festival_id = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        $message = str_replace('{getDiscountByPromoCode}', (string)$data['discount'], self::message_IN_SUCCESS);

        $ticket_type_id = !empty($data['ticket_type_id']) ? new Uuid($data['ticket_type_id']) : null;
        $festival = empty($data['festival_name']) ? 'Мультифестивальный' : $data['festival_name'] . " " . $data['festival_year'];


        return new self(
            LimitPromoCodeDto::fromState($data),
            $message,
            new Uuid($data['id']),
            $data['name'],
            $data['discount'],
            (bool)$data['active'],
            (bool)$data['is_percent'],
            $ticket_type_id,
            $data['ticket_type_name'] ?? null,
            $festival,
        );
    }

    public static function fromGroupTicket(): self
    {
        return new self(
            new LimitPromoCodeDto(),
            self::message_IN_ERROR_FOR_TYPE
        );
    }

    public function getDiscount(): float
    {
        return $this->discount;
    }

    public function get(): string
    {
        return $this->message;
    }

    public function isPercent(): bool
    {
        return $this->isPercent;
    }

    public function setDiscount(float $discount): PromoCodeDto
    {
        $this->discount = $discount;
        $this->message = str_replace('{getDiscountByPromoCode}', (string)$discount, self::message_IN_SUCCESS);


        return $this;
    }

    public function getLimit(): LimitPromoCodeDto
    {
        return $this->limit;
    }

    public function isCorrectForLimit(): bool
    {
        return $this->limit->getCorrect();
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function toArrayForTable(): array
    {
        return [
            'id' => $this->id->value(),
            'name' => $this->name,
            'discount' => $this->discount,
            'is_percent' => $this->isPercent,
            'active' => $this->isSuccess,
            'limit' => $this->limit->getLimit(),
            'ticket_type_id' => $this->ticket_type_id?->value(),
            'festival_id' => $this->festival_id?->value(),
        ];
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    /**
     * @param Uuid|null $id
     */
    public function setId(?Uuid $id): void
    {
        $this->id = $id;
    }

    public function getFestival(): ?string
    {
        return $this->festival;
    }
}
