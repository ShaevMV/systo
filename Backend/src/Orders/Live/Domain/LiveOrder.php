<?php

declare(strict_types=1);

namespace Tickets\Orders\Live\Domain;

use DomainException;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderCancel;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderDifficultiesArose;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaidLiveTicket;
use Tickets\Orders\Live\Dto\LiveOrderDto;
use Tickets\Orders\Live\Pipeline\LiveOrderPipeline;
use Tickets\Orders\Live\Policy\LiveOrderAccessPolicy;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;
use Tickets\Orders\Shared\Specification\CompositeOrderSpecification;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelLiveTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessPushLiveTicket;

/**
 * Живой заказ — покупка карточки live-билета на месте проведения фестиваля.
 *
 * Особенности:
 * - Начальный статус NEW_FOR_LIVE (не NEW)
 * - Выдача номера live-билета при LIVE_TICKET_ISSUED
 * - При отмене освобождает live-номера (ProcessCancelLiveTicket)
 * - Нет email-уведомления при создании (только после оплаты)
 *
 * Переходы статусов определяются LiveOrderPipeline.
 */
final class LiveOrder extends BaseOrder
{
    public function __construct(
        Uuid                    $id,
        Uuid                    $festivalId,
        Uuid                    $userId,
        Status                  $status,
        array                   $tickets,
        private readonly Uuid     $typesOfPaymentId,
        private readonly PriceDto $price,
        private readonly Uuid     $ticketTypeId,
        private readonly string   $phone,
        private readonly ?string  $promoCode = null,
    ) {
        $this->id         = $id;
        $this->festivalId = $festivalId;
        $this->userId     = $userId;
        $this->status     = $status;
        $this->tickets    = $tickets;
    }

    // ----------------------------------------------------------------
    // Фабричный метод — стартует в NEW_FOR_LIVE
    // ----------------------------------------------------------------

    /**
     * Создаёт живой заказ со статусом NEW_FOR_LIVE.
     */
    public static function create(LiveOrderDto $dto, int $kilter): self
    {
        $order = new self(
            id:               $dto->getId(),
            festivalId:       $dto->getFestivalId(),
            userId:           $dto->getUserId(),
            status:           new Status(Status::NEW_FOR_LIVE),
            tickets:          $dto->getTickets(),
            typesOfPaymentId: $dto->getTypesOfPaymentId(),
            price:            $dto->getPriceDto(),
            ticketTypeId:     $dto->getTicketTypeId(),
            phone:            $dto->getPhone(),
            promoCode:        $dto->getPromoCode(),
        );

        $order->recordHistory(new OrderCreatedEvent(
            ticketType: $dto->getTicketTypeId()->value(),
            price:      $dto->getPriceDto()->getTotalPrice(),
            kilter:     $kilter,
        ));

        return $order;
    }

    // ----------------------------------------------------------------
    // Семантические методы смены статуса
    // ----------------------------------------------------------------

    /**
     * Подтверждение оплаты живого билета.
     *
     * Создаёт билеты, отправляет email, рассылает анкеты.
     * НЕ генерирует PDF (билет выдаётся на месте физически).
     */
    public function confirmLivePayment(string $email, int $kilter): void
    {
        $this->changeStatus(new Status(Status::PAID_FOR_LIVE), [
            'email'  => $email,
            'kilter' => $kilter,
        ]);
    }

    /**
     * Выдача live-номеров билетов.
     *
     * @param array $liveNumbers [ticketId => liveNumber]
     */
    public function issueLiveTickets(array $liveNumbers): void
    {
        if (empty($liveNumbers)) {
            throw new DomainException('Забыли ввести номера живых билетов');
        }

        $this->changeStatus(new Status(Status::LIVE_TICKET_ISSUED), [
            'liveNumbers' => $liveNumbers,
        ]);
    }

    /**
     * Отмена заказа (до выдачи live-номеров).
     */
    public function cancelOrder(string $email): void
    {
        $this->updateTicketIds();
        $this->changeStatus(new Status(Status::CANCEL), ['email' => $email]);
    }

    /**
     * Отмена живого заказа (после выдачи live-номеров).
     *
     * Освобождает live-номера через ProcessCancelLiveTicket.
     */
    public function cancelLiveOrder(): void
    {
        $this->updateTicketIds();
        $this->changeStatus(new Status(Status::CANCEL_FOR_LIVE));
    }

    /**
     * Перевод в статус «Возникли трудности».
     */
    public function markDifficultiesArose(string $email, string $comment): void
    {
        $this->updateTicketIds();
        $this->changeStatus(new Status(Status::DIFFICULTIES_AROSE), [
            'email'   => $email,
            'comment' => $comment,
        ]);
    }

    // ----------------------------------------------------------------
    // Реализация BaseOrder
    // ----------------------------------------------------------------

    public function getOrderType(): OrderType
    {
        return OrderType::fromString(OrderType::LIVE);
    }

    protected function createPipeline(): OrderPipelineInterface
    {
        return new LiveOrderPipeline();
    }

    protected function createSpecification(): OrderSpecificationInterface
    {
        return new CompositeOrderSpecification([]);
    }

    protected function createAccessPolicy(): OrderAccessPolicyInterface
    {
        return new LiveOrderAccessPolicy();
    }

    protected function onStatusChanged(Status $from, Status $to, array $params): void
    {
        match ((string)$to) {
            Status::PAID_FOR_LIVE      => $this->firePaidForLiveEvents($params),
            Status::LIVE_TICKET_ISSUED => $this->fireLiveIssuedEvents($params),
            Status::CANCEL             => $this->fireCancelEvents($params),
            Status::CANCEL_FOR_LIVE    => $this->fireCancelLiveEvents(),
            Status::DIFFICULTIES_AROSE => $this->fireDifficultiesAroseEvents($params),
            default                    => null,
        };
    }

    // ----------------------------------------------------------------
    // Приватные методы — Domain Events при переходах
    // ----------------------------------------------------------------

    private function firePaidForLiveEvents(array $params): void
    {
        $this->record(new ProcessCreateTicket($this->id, $this->tickets));

        $this->record(new ProcessUserNotificationOrderPaidLiveTicket(
            $params['email'],
            $this->ticketTypeId,
            $this->typesOfPaymentId,
            $params['kilter'],
        ));

        if (!$this->isChildTicket()) {
            foreach ($this->tickets as $ticket) {
                $this->record(new ProcessGuestNotificationQuestionnaire(
                    $ticket->getEmail() ?? $params['email'],
                    $this->id->value(),
                    $ticket->getId()->value(),
                ));
            }
        }
    }

    private function fireLiveIssuedEvents(array $params): void
    {
        $liveNumbers = $params['liveNumbers'];

        $this->record(new ProcessCreateTicket($this->id, $this->tickets));

        if (!$this->isChildTicket()) {
            foreach ($this->tickets as $ticket) {
                $this->record(new ProcessGuestNotificationQuestionnaire(
                    $ticket->getEmail(),
                    $this->id->value(),
                    $ticket->getId()->value(),
                ));
            }
        }

        foreach ($liveNumbers as $ticketId => $liveNumber) {
            $this->record(new ProcessPushLiveTicket(
                (int)$liveNumber,
                new Uuid($ticketId),
            ));
        }
    }

    private function fireCancelEvents(array $params): void
    {
        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessUserNotificationOrderCancel(
            $params['email'],
            $this->ticketTypeId,
        ));
    }

    private function fireCancelLiveEvents(): void
    {
        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessCancelLiveTicket($this->id, $this->tickets));
    }

    private function fireDifficultiesAroseEvents(array $params): void
    {
        if (empty($params['comment'])) {
            throw new DomainException('Комментарий обязателен для статуса «Возникли трудности»');
        }

        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessUserNotificationOrderDifficultiesArose(
            $this->id,
            $params['email'],
            $params['comment'],
            $this->ticketTypeId,
        ));
    }

    private function updateTicketIds(): void
    {
        foreach ($this->tickets as $guest) {
            $guest->updateId();
        }
    }

    private function isChildTicket(): bool
    {
        return $this->ticketTypeId->value() === BaseOrder::CHILD_TICKET_TYPE_ID;
    }

    // ----------------------------------------------------------------
    // Геттеры специфичные для LiveOrder
    // ----------------------------------------------------------------

    public function getTypesOfPaymentId(): Uuid  { return $this->typesOfPaymentId; }
    public function getPrice(): PriceDto         { return $this->price; }
    public function getTicketTypeId(): Uuid      { return $this->ticketTypeId; }
    public function getPhone(): string           { return $this->phone; }
    public function getPromoCode(): ?string      { return $this->promoCode; }
}
