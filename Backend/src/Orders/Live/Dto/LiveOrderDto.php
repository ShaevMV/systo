<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Dto;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;

/**
 * DTO для создания/гидрации Живого заказа.
 *
 * Живой заказ — покупка живого билета (карточки) с присвоением уникального номера.
 * Создаётся аналогично гостевому, но проходит другой жизненный цикл:
 * NEW_FOR_LIVE → PAID_FOR_LIVE → LIVE_TICKET_ISSUED
 */
final class LiveOrderDto
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
        private readonly ?string  $promoCode = null,
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
    public function getPromoCode(): ?string   { return $this->promoCode; }
}
