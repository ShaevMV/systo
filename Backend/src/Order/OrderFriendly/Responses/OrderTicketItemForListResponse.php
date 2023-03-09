<?php

declare(strict_types=1);

namespace Tickets\Order\OrderFriendly\Responses;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketItemForListResponse extends AbstractionEntity implements Response
{
    protected int $count;
    protected string $humanStatus;

    public function __construct(
        protected Uuid   $id,
        protected int    $kilter,
        protected string $email,
        protected float  $price,
        protected array  $guests,
        protected Status $status,
        protected array  $listCorrectNextStatus,
    )
    {
        $this->count = count($guests);
        $this->humanStatus = $this->status->getHumanStatus();
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
        $status = new Status($data['status']);
        return new self(
            new Uuid($data['id']),
            $data['kilter'],
            $data['email'],
            (float)$data['price'],
            $guests,
            $status,
            $status->getListNextStatus(),
        );
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return GuestsDto[]
     */
    public function getGuests(): array
    {
        return $this->guests;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }
}
