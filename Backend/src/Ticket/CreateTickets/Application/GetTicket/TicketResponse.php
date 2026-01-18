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
        protected ?string $festivalView = null,
        protected ?string $emailView = null,
        protected ?Uuid $festival_id = null,
        protected bool $is_need_seedling = false,
        protected ?Uuid $type_ticket_id = null,
        protected ?string $type_ticket = null,
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
        return $this->festivalView ?? null;
    }

    /**
     * @throws JsonException
     */
    public function toArrayForBaza(): array
    {
        $result = parent::toArray();
        unset($result['festivalView']);
        unset($result['emailView']);

        return $result;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }

    public function getEmailView(): ?string
    {
        return $this->emailView;
    }

    public function isIsNeedSeedling(): bool
    {
        return $this->is_need_seedling;
    }

    public function getTypeTicketId(): ?Uuid
    {
        return $this->type_ticket_id;
    }

    public function getTypeTicket(): ?string
    {
        return $this->type_ticket;
    }
}
