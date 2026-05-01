<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Dto;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;

/**
 * DTO для создания Дружеского заказа.
 *
 * Дружеский заказ создаётся пушером от имени гостя.
 * Гость не имеет личного кабинета, получает только email с билетом.
 * Заказ сразу переходит в статус PAID при создании.
 */
final class FriendlyOrderDto
{
    /**
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        private readonly Uuid     $id,
        private readonly Uuid     $festivalId,
        private readonly Uuid     $pusherId,
        private readonly string   $email,
        private readonly Uuid     $ticketTypeId,
        private readonly array    $tickets,
        private readonly PriceDto $priceDto,
        private readonly ?string  $comment   = null,
    ) {}

    public function getId(): Uuid          { return $this->id; }
    public function getFestivalId(): Uuid  { return $this->festivalId; }
    public function getPusherId(): Uuid    { return $this->pusherId; }
    public function getEmail(): string     { return $this->email; }
    public function getTicketTypeId(): Uuid { return $this->ticketTypeId; }
    public function getTickets(): array    { return $this->tickets; }
    public function getPriceDto(): PriceDto { return $this->priceDto; }
    public function getComment(): ?string  { return $this->comment; }
}
