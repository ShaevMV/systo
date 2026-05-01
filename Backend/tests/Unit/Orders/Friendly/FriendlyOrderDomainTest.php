<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Friendly;

use DomainException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\History\Domain\Event\OrderStatusChangedEvent;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;
use Tickets\Ticket\CreateTickets\Domain\ProcessCancelTicket;
use Tickets\Ticket\CreateTickets\Domain\ProcessCreateTicket;

/**
 * Тесты доменной логики FriendlyOrder.
 *
 * Проверяет: создание сразу в PAID, правильные Domain Events,
 * запись истории (OrderCreated + StatusChanged NEW→PAID),
 * отмену, защиту от недопустимых переходов.
 */
class FriendlyOrderDomainTest extends TestCase
{
    private const FESTIVAL_ID   = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const PUSHER_ID     = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    private const TICKET_TYPE   = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

    // ----------------------------------------------------------------
    // Создание заказа
    // ----------------------------------------------------------------

    /** @test */
    public function create_returns_friendly_order_with_paid_status(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);

        $this->assertSame(Status::PAID, (string)$order->getStatus());
    }

    /** @test */
    public function create_records_create_ticket_event(): void
    {
        $order  = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $events = $order->pullDomainEvents();

        $eventClasses = array_map('get_class', $events);
        $this->assertContains(ProcessCreateTicket::class, $eventClasses);
    }

    /** @test */
    public function create_records_paid_friendly_notification_event(): void
    {
        $order  = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $events = $order->pullDomainEvents();

        $eventClasses = array_map('get_class', $events);
        $this->assertContains(
            \Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderPaidFriendly::class,
            $eventClasses
        );
    }

    /** @test */
    public function create_records_order_created_history_event(): void
    {
        $order   = FriendlyOrder::create($this->makeDto(), kilter: 5);
        $history = $order->pullHistoryEvents();

        $historyClasses = array_map('get_class', $history);
        $this->assertContains(OrderCreatedEvent::class, $historyClasses);
    }

    /** @test */
    public function create_records_status_changed_history_event_from_new_to_paid(): void
    {
        $order   = FriendlyOrder::create($this->makeDto(), kilter: 5);
        $history = $order->pullHistoryEvents();

        $statusEvents = array_filter(
            $history,
            fn($e) => $e instanceof OrderStatusChangedEvent
        );

        $this->assertNotEmpty($statusEvents, 'Должно быть событие OrderStatusChangedEvent');

        $event = array_values($statusEvents)[0];
        $payload = $event->getPayload();
        $this->assertSame(Status::NEW, $payload['from']);
        $this->assertSame(Status::PAID, $payload['to']);
    }

    /** @test */
    public function order_type_is_friendly(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $this->assertSame(OrderType::FRIENDLY, $order->getOrderType()->value());
    }

    /** @test */
    public function user_id_equals_pusher_id(): void
    {
        $dto   = $this->makeDto();
        $order = FriendlyOrder::create($dto, kilter: 1);

        $this->assertTrue($order->getUserId()->equals($dto->getPusherId()));
    }

    // ----------------------------------------------------------------
    // Отмена
    // ----------------------------------------------------------------

    /** @test */
    public function cancel_order_changes_status_to_cancel(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents();

        $order->cancelOrder('pusher@example.com');

        $this->assertSame(Status::CANCEL, (string)$order->getStatus());
    }

    /** @test */
    public function cancel_records_cancel_ticket_event(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents();

        $order->cancelOrder('pusher@example.com');
        $events = $order->pullDomainEvents();

        $eventClasses = array_map('get_class', $events);
        $this->assertContains(ProcessCancelTicket::class, $eventClasses);
    }

    /** @test */
    public function cancel_is_terminal_cannot_transition_further(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);
        $order->cancelOrder('pusher@example.com');
        $order->pullDomainEvents();

        $this->expectException(DomainException::class);
        $order->cancelOrder('pusher@example.com');
    }

    // ----------------------------------------------------------------
    // Доступные переходы
    // ----------------------------------------------------------------

    /** @test */
    public function paid_friendly_order_has_one_available_transition(): void
    {
        $order = FriendlyOrder::create($this->makeDto(), kilter: 1);

        $transitions = $order->getAvailableTransitions();

        $this->assertCount(1, $transitions);
        $this->assertArrayHasKey(Status::CANCEL, $transitions);
    }

    /** @test */
    public function child_ticket_type_id_accessible_via_base_order(): void
    {
        $this->assertSame(
            'c3d4e5f6-a7b8-9012-cdef-345678901235',
            BaseOrder::CHILD_TICKET_TYPE_ID
        );
    }

    // ----------------------------------------------------------------

    private function makeDto(): FriendlyOrderDto
    {
        return new FriendlyOrderDto(
            id:           Uuid::random(),
            festivalId:   new Uuid(self::FESTIVAL_ID),
            pusherId:     new Uuid(self::PUSHER_ID),
            email:        'guest@example.com',
            ticketTypeId: new Uuid(self::TICKET_TYPE),
            tickets:      [
                GuestsDto::fromState([
                    'value'       => 'Гость Пушера',
                    'email'       => 'guest@example.com',
                    'number'      => null,
                    'id'          => Uuid::random()->value(),
                    'festival_id' => self::FESTIVAL_ID,
                ]),
            ],
            priceDto:     new PriceDto(3800, 1, 0),
            comment:      'Тестовый дружеский заказ',
        );
    }
}
