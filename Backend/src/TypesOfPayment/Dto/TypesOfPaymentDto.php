<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Carbon\Carbon;
use Nette\Utils\JsonException;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TypesOfPaymentDto extends AbstractionEntity implements Response
{
    public function __construct(
        protected string  $name,
        protected bool    $active,
        protected int     $sort,
        protected bool    $is_billing,
        protected Uuid    $id,
        protected ?string $card = null,
        protected ?Uuid   $user_external_id = null,
        protected ?string $email_seller = null,
        protected ?Carbon $created_at = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        return new self(
            $data['name'],
            boolval($data['active']),
            $data['sort'],
            boolval($data['is_billing']),
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            $data['card'] ?? null,
            empty($data['user_external_id']) ? null : new Uuid($data['user_external_id']),
            $data['email_seller'] ?? null,
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
        );
    }

    /**
     * @throws JsonException
     */
    public function toArrayForEdit(): array
    {
        $result = parent::toArray();

        unset($result['id']);

        return $result;
    }

    public function toArrayForCreate(): array
    {
        $result = parent::toArray();

        unset($result['id'], $result['email_seller'], $result['created_at']);

        return $result;
    }

    /**
     * @return Uuid
     */
    public function getId(): Uuid
    {
        return $this->id;
    }
}
