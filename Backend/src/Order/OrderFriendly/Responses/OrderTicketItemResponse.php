<?php

declare(strict_types=1);

namespace Tickets\Order\OrderFriendly\Responses;

use Carbon\Carbon;
use Nette\Utils\Json;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\Shared\Responses\BaseOrderTicketItemResponse;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class OrderTicketItemResponse extends AbstractionEntity implements Response, BaseOrderTicketItemResponse
{
    protected float $totalPrice = 0.00;
    protected int $count;
    protected string $humanStatus;

    /**
     * @param  Uuid  $id
     * @param  Uuid  $userId
     * @param  int  $kilter
     * @param  float  $price
     * @param  array  $guests
     * @param  Status  $status
     * @param  Carbon  $dateCreate
     * @param  string  $email
     * @param  TicketDto[]  $tickets
     */
    public function __construct(
        protected Uuid $id,
        protected Uuid $userId,
        protected int $kilter,
        protected float $price,
        protected array $guests,
        protected Status $status,
        protected Carbon $dateCreate,
        protected string $email,
        protected array $tickets,
    ) {
        $this->totalPrice = $price;
        $this->count = count($this->guests);
        $this->humanStatus = $this->status->getHumanStatus();
    }

    public static function fromState(array $data): self
    {
        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);
        $tickets = [];
        foreach ($data['tickets'] as $ticket) {
            $tickets[] = TicketDto::fromState($ticket);
        }

        return new self(
            new Uuid($data['id']),
            new Uuid($data['user_id']),
            $data['kilter'],
            $data['price'],
            $guests,
            new Status($data['status']),
            new Carbon($data['created_at']),
            $data['users']['email'],
            $tickets,
        );
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
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
