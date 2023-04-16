<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Carbon\Carbon;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TicketResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected string $name,
        protected int    $kilter,
        protected Uuid   $uuid,
        protected string $email,
        protected string $phone,
        protected string $city,
        protected Carbon $data_order,
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

    /**
     * @return Carbone
     */
    public function getDataOrder(): Carbone
    {
        return $this->dataOrder;
    }
}
