<?php

declare(strict_types=1);

namespace Tickets\Order\Shared\Domain;

use Nette\Utils\JsonException;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Order\Shared\Dto\PriceDto;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

abstract class BaseOrderTicketDto
{
    protected Uuid $id;

    /**
     * @param Uuid $festival_id
     * @param Uuid $user_id
     * @param string $email
     * @param GuestsDto[] $ticket
     * @param PriceDto $priceDto
     * @param Status|null $status
     * @param Uuid|null $id
     */
    public function __construct(
        protected Uuid     $festival_id,
        protected Uuid     $user_id,
        protected string   $email,
        protected array    $ticket,
        protected PriceDto $priceDto,
        protected ?Status  $status,
        ?Uuid              $id = null,
    )
    {
        $this->id = $id ?? Uuid::random();
    }

    /**
     * @throws JsonException
     */
    abstract public static function fromState(
        array    $data,
        Uuid     $userId,
        PriceDto $priceDto
    ): self;


    /**
     * @throws JsonException
     */
    abstract public function toArray(): array;

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }

    /**
     * @return Uuid
     */
    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    /**
     * @return PriceDto
     */
    public function getPriceDto(): PriceDto
    {
        return $this->priceDto;
    }

    /**
     * @return Status|null
     */
    public function getStatus(): ?Status
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getTicket(): array
    {
        return $this->ticket;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }
}
