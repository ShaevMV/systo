<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Dto;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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
        protected SellerDto $seller,
        protected TicketTypeDto $ticket_type,
        protected ?string $card = null,
        protected ?Carbon $created_at = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        Log::info('\Tickets\TypesOfPayment\Dto\TypesOfPaymentDto::fromState', $data);
        if(is_string($data['active'])) {
            $active = $data['active'] == "true";
            $is_billing = $data['is_billing'] == "true";
        } else {
            $active = (bool)$data['active'];
            $is_billing = (bool)$data['is_billing'];
        }

        Log::info('активе ', [
            'active' => $active,
            'is_billing' => $is_billing
        ]);
        return new self(
            $data['name'],
            $active,
            $data['sort'],
            $is_billing,
            empty($data['id']) ? Uuid::random() : new Uuid($data['id']),
            SellerDto::fromState($data),
            TicketTypeDto::fromState($data),
            $data['card'] ?? '',
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
        );
    }

    /**
     * @throws JsonException
     */
    public function toArrayForEdit(): array
    {
        $result = parent::toArrayForEdit();

        unset(
            $result['seller'],
            $result['ticket_type'],
            $result['created_at'],
        );
        $result['user_external_id'] = $this->seller->getUserExternalId()?->value();
        $result['ticket_type_id'] = $this->ticket_type->getTicketTypeId()?->value();

        return $result;
    }

    public function toArrayForCreate(): array
    {
        $result = parent::toArrayForCreate();

        unset($result['seller'], $result['ticket_type']);
        $result['user_external_id'] = $this->seller->getUserExternalId()?->value();
        $result['ticket_type_id'] = $this->ticket_type->getTicketTypeId()?->value();

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
