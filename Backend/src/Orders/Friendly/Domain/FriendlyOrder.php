<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Domain;

use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderCancel;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaidFriendly;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Friendly\Pipeline\FriendlyOrderPipeline;
use Tickets\Orders\Friendly\Policy\FriendlyOrderAccessPolicy;
use Tickets\Orders\Shared\Contract\OrderAccessPolicyInterface;
use Tickets\Orders\Shared\Contract\OrderPipelineInterface;
use Tickets\Orders\Shared\Contract\OrderSpecificationInterface;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;
use Tickets\Orders\Shared\Specification\CompositeOrderSpecification;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessGuestNotificationQuestionnaire;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessTelegramByQuestionnaireSend;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

/**
 * Дружеский заказ — создаётся пушером от имени гостя.
 *
 * Особенности:
 * - Нет этапа NEW: создаётся сразу в статусе PAID
 * - Гость не имеет личного кабинета (ProcessUserNotificationOrderPaidFriendly)
 * - Владелец заказа — пушер (userId = pusherId)
 * - Нет формы оплаты: цена вводится пушером вручную
 *
 * Переходы статусов определяются FriendlyOrderPipeline:
 *   PAID → CANCEL
 */
final class FriendlyOrder extends BaseOrder
{
    public function __construct(
        Uuid                    $id,
        Uuid                    $festivalId,
        Uuid                    $userId,
        Status                  $status,
        array                   $tickets,
        private readonly Uuid     $ticketTypeId,
        private readonly PriceDto $price,
    ) {
        $this->id         = $id;
        $this->festivalId = $festivalId;
        $this->userId     = $userId;
        $this->status     = $status;
        $this->tickets    = $tickets;
    }

    // ----------------------------------------------------------------
    // Фабричный метод — сразу в статусе PAID
    // ----------------------------------------------------------------

    /**
     * Создаёт дружеский заказ.
     *
     * Дружеский заказ не проходит через NEW: сразу подтверждается пушером.
     * Записывает историю создания и перехода в PAID.
     */
    public static function create(FriendlyOrderDto $dto, int $kilter): self
    {
        $order = new self(
            id:           $dto->getId(),
            festivalId:   $dto->getFestivalId(),
            userId:       $dto->getPusherId(),
            status:       new Status(Status::PAID),
            tickets:      $dto->getTickets(),
            ticketTypeId: $dto->getTicketTypeId(),
            price:        $dto->getPriceDto(),
        );

        $order->record(new ProcessCreateTicket($order->id, $order->tickets));

        $order->record(new ProcessUserNotificationOrderPaidFriendly(
            $dto->getEmail(),
            $order->tickets,
            $dto->getTicketTypeId(),
            $dto->getComment(),
        ));

        if (!$order->isChildTicket()) {
            foreach ($order->tickets as $ticket) {
                $guestEmail = $ticket->getEmail() ?? $dto->getEmail();
                $order->record(new ProcessGuestNotificationQuestionnaire(
                    $guestEmail,
                    $order->id->value(),
                    $ticket->getId()->value(),
                ));
                $order->record(new ProcessTelegramByQuestionnaireSend($guestEmail));
            }
        }

        $order->recordHistory(new OrderCreatedEvent(
            ticketType: $dto->getTicketTypeId()->value(),
            price:      $dto->getPriceDto()->getTotalPrice(),
            kilter:     $kilter,
        ));

        $order->recordHistory(new OrderStatusChangedEvent(
            fromStatus: Status::NEW,
            toStatus:   Status::PAID,
        ));

        return $order;
    }

    // ----------------------------------------------------------------
    // Семантический метод смены статуса
    // ----------------------------------------------------------------

    /**
     * Отмена дружеского заказа.
     *
     * Аннулирует билеты, отправляет email об отмене.
     */
    public function cancelOrder(string $email): void
    {
        $this->changeStatus(new Status(Status::CANCEL), ['email' => $email]);
    }

    // ----------------------------------------------------------------
    // Реализация BaseOrder
    // ----------------------------------------------------------------

    public function getOrderType(): OrderType
    {
        return OrderType::fromString(OrderType::FRIENDLY);
    }

    protected function createPipeline(): OrderPipelineInterface
    {
        return new FriendlyOrderPipeline();
    }

    protected function createSpecification(): OrderSpecificationInterface
    {
        return new CompositeOrderSpecification([]);
    }

    protected function createAccessPolicy(): OrderAccessPolicyInterface
    {
        return new FriendlyOrderAccessPolicy();
    }

    protected function onStatusChanged(Status $from, Status $to, array $params): void
    {
        match ((string)$to) {
            Status::CANCEL => $this->fireCancelEvents($params),
            default        => null,
        };
    }

    private function fireCancelEvents(array $params): void
    {
        $this->record(new ProcessCancelTicket($this->id));
        $this->record(new ProcessUserNotificationOrderCancel(
            $params['email'],
            $this->ticketTypeId,
        ));
    }

    private function isChildTicket(): bool
    {
        return $this->ticketTypeId->value() === BaseOrder::CHILD_TICKET_TYPE_ID;
    }

    // ----------------------------------------------------------------
    // Геттеры специфичные для FriendlyOrder
    // ----------------------------------------------------------------

    public function getTicketTypeId(): Uuid  { return $this->ticketTypeId; }
    public function getPrice(): PriceDto     { return $this->price; }
}
