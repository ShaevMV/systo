<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Guest;

use DomainException;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\History\Domain\Event\OrderCreatedEvent;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Shared\Domain\BaseOrder;
use Tickets\Orders\Shared\Domain\ValueObject\OrderType;

/**
 * Тесты доменной логики GuestOrder.
 *
 * Проверяет: создание заказа, начальный статус, Domain Events,
 * переходы статусов, защиту от недопустимых переходов,
 * идентификатор типа заказа и общий CHILD_TICKET_TYPE_ID.
 */
class GuestOrderDomainTest extends TestCase
{
    private const FESTIVAL_ID = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
    private const USER_ID     = 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb';
    private const PAYMENT_ID  = 'cccccccc-cccc-cccc-cccc-cccccccccccc';
    private const TICKET_TYPE = 'dddddddd-dddd-dddd-dddd-dddddddddddd';

    // ----------------------------------------------------------------
    // Создание заказа
    // ----------------------------------------------------------------

    /** @test */
    public function create_returns_guest_order_with_new_status(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1042);

        $this->assertSame(Status::NEW, (string)$order->getStatus());
    }

    /** @test */
    public function create_records_notification_domain_event(): void
    {
        $order  = GuestOrder::create($this->makeDto(), kilter: 1042);
        $events = $order->pullDomainEvents();

        $this->assertNotEmpty($events);
        // Первое событие — уведомление о создании
        $this->assertInstanceOf(
            \Tickets\Order\OrderTicket\Domain\ProcessUserNotificationNewOrderTicket::class,
            $events[0]
        );
    }

    /** @test */
    public function create_records_order_created_history_event(): void
    {
        $order  = GuestOrder::create($this->makeDto(), kilter: 1042);
        $history = $order->pullHistoryEvents();

        $this->assertNotEmpty($history);
        $this->assertInstanceOf(OrderCreatedEvent::class, $history[0]);
    }

    /** @test */
    public function order_type_is_guest(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);

        $this->assertSame(OrderType::GUEST, $order->getOrderType()->value());
    }

    /** @test */
    public function child_ticket_type_id_is_defined_in_base_order(): void
    {
        // Константа должна быть в BaseOrder, а не в GuestOrder
        $this->assertSame(
            'c3d4e5f6-a7b8-9012-cdef-345678901235',
            BaseOrder::CHILD_TICKET_TYPE_ID
        );
    }

    // ----------------------------------------------------------------
    // Переходы статусов
    // ----------------------------------------------------------------

    /** @test */
    public function confirm_payment_changes_status_to_paid(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents(); // очищаем события создания

        $order->confirmPayment('user@example.com');

        $this->assertSame(Status::PAID, (string)$order->getStatus());
    }

    /** @test */
    public function confirm_payment_records_domain_events(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents();

        $order->confirmPayment('user@example.com');
        $events = $order->pullDomainEvents();

        $this->assertNotEmpty($events);
    }

    /** @test */
    public function cancel_order_changes_status_to_cancel(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents();

        $order->cancelOrder('user@example.com');

        $this->assertSame(Status::CANCEL, (string)$order->getStatus());
    }

    /** @test */
    public function mark_difficulties_arose_requires_status_change(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->pullDomainEvents();

        $order->markDifficultiesArose('user@example.com', 'Проблема с оплатой');

        $this->assertSame(Status::DIFFICULTIES_AROSE, (string)$order->getStatus());
    }

    /** @test */
    public function invalid_transition_throws_domain_exception(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->confirmPayment('user@example.com'); // PAID
        $order->pullDomainEvents();

        $this->expectException(DomainException::class);

        // PAID → CANCEL недопустимо (только через DIFFICULTIES_AROSE)
        $order->cancelOrder('user@example.com');
    }

    /** @test */
    public function terminal_cancel_status_rejects_all_transitions(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->cancelOrder('user@example.com');
        $order->pullDomainEvents();

        $this->expectException(DomainException::class);
        $order->confirmPayment('user@example.com');
    }

    // ----------------------------------------------------------------
    // Доступные переходы
    // ----------------------------------------------------------------

    /** @test */
    public function new_order_has_three_available_transitions(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);

        $transitions = $order->getAvailableTransitions();

        $this->assertCount(3, $transitions);
    }

    /** @test */
    public function cancelled_order_has_no_available_transitions(): void
    {
        $order = GuestOrder::create($this->makeDto(), kilter: 1);
        $order->cancelOrder('user@example.com');

        $transitions = $order->getAvailableTransitions();

        $this->assertEmpty($transitions);
    }

    // ----------------------------------------------------------------
    // Геттеры
    // ----------------------------------------------------------------

    /** @test */
    public function getters_return_correct_values(): void
    {
        $dto   = $this->makeDto();
        $order = GuestOrder::create($dto, kilter: 1);

        $this->assertTrue($order->getId()->equals($dto->getId()));
        $this->assertTrue($order->getFestivalId()->equals($dto->getFestivalId()));
        $this->assertTrue($order->getUserId()->equals($dto->getUserId()));
        $this->assertSame($dto->getPhone(), $order->getPhone());
        $this->assertNull($order->getPromoCode());
    }

    // ----------------------------------------------------------------

    private function makeDto(?Uuid $ticketTypeId = null): GuestOrderDto
    {
        return new GuestOrderDto(
            id:               Uuid::random(),
            festivalId:       new Uuid(self::FESTIVAL_ID),
            userId:           new Uuid(self::USER_ID),
            email:            'user@example.com',
            phone:            '+79991234567',
            typesOfPaymentId: new Uuid(self::PAYMENT_ID),
            ticketTypeId:     $ticketTypeId ?? new Uuid(self::TICKET_TYPE),
            tickets:          [
                GuestsDto::fromState([
                    'value'       => 'Тестовый Гость',
                    'email'       => 'guest@example.com',
                    'number'      => null,
                    'id'          => Uuid::random()->value(),
                    'festival_id' => self::FESTIVAL_ID,
                ]),
            ],
            priceDto:         new PriceDto(3800, 1, 0),
            status:           new Status(Status::NEW),
        );
    }
}
