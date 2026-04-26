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

class OrderTicketItemForCuratorListResponse extends AbstractionEntity implements Response
{
    protected int $count;
    protected string $humanStatus;

    public function __construct(
        protected Uuid    $id,
        protected int     $kilter,
        protected string  $email,
        protected string  $name,
        protected int     $price,
        protected array   $guests,
        protected Status  $status,
        protected array   $listCorrectNextStatus,
        protected ?string $phone = null,
        protected ?string $pusher_name = null,
        protected ?string $pusher_email = null,
        protected ?Uuid   $userId = null,
        protected ?string $project = null,
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
            $guests[] = GuestsDto::fromState($guest, $data['festival_id']);
        }
        $status = new Status($data['status']);
        $userId = empty($data['user_id']) ? null : new Uuid($data['user_id']);

        return new self(
            new Uuid($data['id']),
            $data['kilter'],
            $guests[0]->getEmail(),
            $data['name'],
            (int)$data['price'],
            $guests,
            $status,
            $status->getListNextStatus(),
            $data['phone'] ?? null,
            $data['pusher_name'] ?? null,
            $data['pusher_email'] ?? null,
            $userId,
            $data['project'] ?? null,
        );
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
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

    public function setGuests(array $guests): self
    {
        $this->guests = $guests;
        $this->count = count($guests);
        return $this;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }
}
