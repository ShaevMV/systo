<?php

declare(strict_types=1);

namespace Tickets\OptionPrice\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Волна цены опции к билету (v2.6.0).
 *
 * Полный аналог `TicketTypePriceDto`, но цена хранится в INT (рубли
 * целиком, без копеек) — фестиваль не оперирует копейками.
 *
 * См. `.claude/specs/ticket-options.md`.
 */
class OptionPriceDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected Uuid $option_id,
        protected int $price,
        protected Carbon $before_date,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            new Uuid($data['option_id']),
            (int) $data['price'],
            new Carbon($data['before_date']),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOptionId(): Uuid
    {
        return $this->option_id;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getBeforeDate(): Carbon
    {
        return $this->before_date;
    }
}
