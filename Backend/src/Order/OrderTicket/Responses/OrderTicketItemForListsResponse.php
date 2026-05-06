<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;

class OrderTicketItemForListsResponse extends AbstractionEntity implements Response
{
    protected int $count;
    protected string $humanStatus;

    /**
     * @param GuestsDto[] $guests
     */
    public function __construct(
        protected Uuid    $id,
        protected int     $kilter,
        protected string  $email,
        protected array   $guests,
        protected Status  $status,
        protected array   $listCorrectNextStatus,
        protected ?string $phone = null,
        protected ?string $curator_name = null,
        protected ?string $curator_email = null,
        protected ?string $location_name = null,
        protected ?string $festival_name = null,
        protected ?string $project = null,
        protected ?Uuid   $userId = null,
        protected ?Uuid   $locationId = null,
        protected ?Uuid   $curatorId = null,
        protected ?Uuid   $festivalId = null,
    ) {
        $this->count = count($guests);
        $this->humanStatus = $this->status->getHumanStatus();
    }

    /**
     * @throws JsonException
     */
    public static function fromState(array $data): self
    {
        $guestsRaw = ! is_array($data['guests']) ? Json::decode($data['guests'], 1) : $data['guests'];
        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = GuestsDto::fromState($guest, $data['festival_id']);
        }
        $status = new Status($data['status']);

        return new self(
            new Uuid($data['id']),
            (int) $data['kilter'],
            $data['email'] ?? '',
            $guests,
            $status,
            $status->getListNextStatus(),
            $data['phone'] ?? null,
            $data['curator_name']  ?? null,
            $data['curator_email'] ?? null,
            $data['location_name'] ?? null,
            $data['festival_name'] ?? null,
            $data['project'] ?? null,
            empty($data['user_id'])    ? null : new Uuid($data['user_id']),
            empty($data['location_id']) ? null : new Uuid($data['location_id']),
            empty($data['curator_id'])  ? null : new Uuid($data['curator_id']),
            empty($data['festival_id']) ? null : new Uuid($data['festival_id']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getPrice(): int
    {
        return 0;
    }

    public function getDiscount(): int
    {
        return 0;
    }

    /**
     * @return GuestsDto[]
     */
    public function getGuests(): array
    {
        return $this->guests;
    }

    public function setGuests(array $guests): self
    {
        $this->guests = $guests;
        $this->count = count($guests);

        return $this;
    }

    public function getGuestsByFestivalId(Uuid $festivalId): array
    {
        $result = [];
        foreach ($this->guests as $guest) {
            if ($guest->getFestivalId()->equals($festivalId)) {
                $result[] = $guest;
            }
        }

        return $result;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curatorId;
    }
}
