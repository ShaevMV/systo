<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Dto;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;

/**
 * DTO для создания/гидрации Гостевого заказа.
 *
 * Гостевой заказ — покупка через стандартную форму сайта.
 * Привязан к аккаунту пользователя, виден в его личном кабинете.
 */
final class GuestOrderDto
{
    /**
     * @param GuestsDto[] $tickets
     */
    public function __construct(
        private readonly Uuid     $id,
        private readonly Uuid     $festivalId,
        private readonly Uuid     $userId,
        private readonly string   $email,
        private readonly string   $phone,
        private readonly Uuid     $typesOfPaymentId,
        private readonly Uuid     $ticketTypeId,
        private readonly array    $tickets,
        private readonly PriceDto $priceDto,
        private readonly Status   $status,
        private readonly ?string  $promoCode  = null,
        private readonly ?Uuid    $inviteLink = null,
    ) {}

    public function getId(): Uuid             { return $this->id; }
    public function getFestivalId(): Uuid     { return $this->festivalId; }
    public function getUserId(): Uuid         { return $this->userId; }
    public function getEmail(): string        { return $this->email; }
    public function getPhone(): string        { return $this->phone; }
    public function getTypesOfPaymentId(): Uuid { return $this->typesOfPaymentId; }
    public function getTicketTypeId(): Uuid   { return $this->ticketTypeId; }
    public function getTickets(): array       { return $this->tickets; }
    public function getPriceDto(): PriceDto   { return $this->priceDto; }
    public function getStatus(): Status       { return $this->status; }
    public function getPromoCode(): ?string   { return $this->promoCode; }
    public function getInviteLink(): ?Uuid    { return $this->inviteLink; }
}
