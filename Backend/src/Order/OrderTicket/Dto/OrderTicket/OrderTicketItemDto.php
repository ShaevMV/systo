<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Carbon\Carbon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketItemDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $name,
        protected array $guests,
        protected Carbon $date,
        protected Status $status,
        protected ?string $last_comment = null,
        protected ?string $promo_code = null,
    ) {
    }


    /**
     * @throws JsonException
     */
    public static function fromState(array $data): self
    {
        $guestsRaw = !is_array($data['guests']) ? Json::decode($data['guests'], 1) : $data['guests'];
        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = GuestsDto::fromState($guest);
        }

        return new self(
            new Uuid($data['id']),
            $data['email'],
            $data['name'],
            $guests,
            new Carbon($data['date']),
            new Status($data['status']),
            $data['last_comment'],
            $data['promo_code'],
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getGuests(): array
    {
        return $this->guests;
    }

    public function getDate(): Carbon
    {
        return $this->date;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getLastComment(): ?string
    {
        return $this->last_comment;
    }

    public function getPromoCode(): ?string
    {
        return $this->promo_code;
    }
}
