<?php

declare(strict_types=1);

namespace Tickets\TicketType\Dto;

use Carbon\Carbon;
use Nette\Utils\JsonException;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketTypeDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $name,
        protected int $price,
        protected int $sort,
        protected bool $active,
        protected bool $is_live_ticket,
        protected bool $is_parking,
        protected FestivalDto $festival,
        protected ?Uuid $questionnaire_type_id = null,
        protected ?int $groupLimit = null,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['name'],
            (int)($data['current_price'] ?? $data['price']),
            (int)$data['sort'],
            (bool)$data['active'],
            (bool)($data['is_live_ticket'] ?? false),
            (bool)($data['is_parking'] ?? false),
            FestivalDto::fromState($data),
            empty($data['questionnaire_type_id']) ? null : new Uuid($data['questionnaire_type_id']),
            $data['groupLimit'] ?? null,
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
        );
    }

    /**
     * @throws JsonException
     */
    public function toArrayForEdit(): array
    {
        $result =  parent::toArrayForEdit();
        unset(
            $result['festival']
        );

        return  $result;
    }

    public function toArrayForCreate(): array
    {
        $result =  parent::toArrayForCreate();
        unset(
            $result['festival'], $result['festival_id'],
        );

        return  $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }


    public function getFestival(): FestivalDto
    {
        return $this->festival;
    }

    public function getQuestionnaireTypeId(): ?Uuid
    {
        return $this->questionnaire_type_id;
    }
}
