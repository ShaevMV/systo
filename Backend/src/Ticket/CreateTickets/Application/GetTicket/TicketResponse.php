<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Application\GetTicket;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\ValueObject\Uuid;

class TicketResponse implements Response
{
    public function __construct(
        private string $name,
        private int    $kilter,
        private Uuid   $id,
        private string $email,
        private string $phone,
        private string $city,
    ){
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
        return $this->id;
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
}
