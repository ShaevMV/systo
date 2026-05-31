<?php

declare(strict_types=1);

namespace Tickets\Option\Dto;

use Carbon\Carbon;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * DTO опции к билету.
 *
 * Соответствует записи в таблице `options`. Цена опции хранится
 * отдельно (волны цен в `option_price`), описание — на pivot
 * (зависит от типа билета).
 *
 * Привязка к типам билетов передаётся отдельным аргументом в
 * `OptionApplication::create/edit` — массив `OptionTicketTypeBindingDto`.
 *
 * См. `.claude/specs/ticket-options.md` §3 (Доменная модель).
 */
class OptionDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected bool $active,
        protected Uuid $festival_id,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    ) {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['name'],
            (bool) ($data['active'] ?? true),
            new Uuid($data['festival_id']),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }
}
