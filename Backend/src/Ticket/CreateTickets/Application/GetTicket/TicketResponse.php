<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Carbon\Carbon;
use Nette\Utils\JsonException;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

class TicketResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected string $name,
        protected int    $kilter,
        protected Uuid   $uuid,
        protected string $status,
        protected string $email,
        protected string $phone,
        protected string $city,
        protected ?string $comment,
        protected Carbon $date_order,
        protected ?string $festivalView,
        protected ?Uuid $festival_id = null
    )
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }

    public function getId(): Uuid
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getFestivalView(): ?string
    {
        return $this->festivalView;
    }

    /**
     * @throws JsonException
     */
    public function toArrayForBaza(): array
    {
        $result = parent::toArray();
        unset($result['festivalView']);

        return $result;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }
}
