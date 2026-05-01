<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Guest;

use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Guest\Pipeline\GuestOrderPipeline;

/**
 * Тесты матрицы переходов GuestOrderPipeline.
 *
 * Проверяет все допустимые и недопустимые переходы статусов
 * для гостевого заказа.
 */
class GuestOrderPipelineTest extends TestCase
{
    private GuestOrderPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new GuestOrderPipeline();
    }

    // ----------------------------------------------------------------
    // Допустимые переходы
    // ----------------------------------------------------------------

    /** @test */
    public function new_can_transition_to_paid(): void
    {
        $order = $this->orderWithStatus(Status::NEW);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::PAID)));
    }

    /** @test */
    public function new_can_transition_to_cancel(): void
    {
        $order = $this->orderWithStatus(Status::NEW);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::CANCEL)));
    }

    /** @test */
    public function new_can_transition_to_difficulties_arose(): void
    {
        $order = $this->orderWithStatus(Status::NEW);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::DIFFICULTIES_AROSE)));
    }

    /** @test */
    public function paid_can_transition_to_difficulties_arose(): void
    {
        $order = $this->orderWithStatus(Status::PAID);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::DIFFICULTIES_AROSE)));
    }

    /** @test */
    public function difficulties_arose_can_transition_to_paid(): void
    {
        $order = $this->orderWithStatus(Status::DIFFICULTIES_AROSE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::PAID)));
    }

    /** @test */
    public function difficulties_arose_can_transition_to_cancel(): void
    {
        $order = $this->orderWithStatus(Status::DIFFICULTIES_AROSE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::CANCEL)));
    }

    // ----------------------------------------------------------------
    // Недопустимые переходы
    // ----------------------------------------------------------------

    /** @test */
    public function cancel_is_terminal_no_transitions_allowed(): void
    {
        $order = $this->orderWithStatus(Status::CANCEL);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::DIFFICULTIES_AROSE)));
    }

    /** @test */
    public function paid_cannot_transition_to_new(): void
    {
        $order = $this->orderWithStatus(Status::PAID);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW)));
    }

    /** @test */
    public function paid_cannot_transition_to_cancel_directly(): void
    {
        $order = $this->orderWithStatus(Status::PAID);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::CANCEL)));
    }

    /** @test */
    public function live_statuses_not_allowed_in_guest_pipeline(): void
    {
        $order = $this->orderWithStatus(Status::NEW);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW_FOR_LIVE)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID_FOR_LIVE)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::LIVE_TICKET_ISSUED)));
    }

    // ----------------------------------------------------------------
    // Доступные переходы
    // ----------------------------------------------------------------

    /** @test */
    public function new_status_returns_three_available_transitions(): void
    {
        $order       = $this->orderWithStatus(Status::NEW);
        $transitions = $this->pipeline->getAvailableTransitions($order);

        $this->assertCount(3, $transitions);
        $this->assertArrayHasKey(Status::PAID, $transitions);
        $this->assertArrayHasKey(Status::CANCEL, $transitions);
        $this->assertArrayHasKey(Status::DIFFICULTIES_AROSE, $transitions);
    }

    /** @test */
    public function cancel_status_returns_no_available_transitions(): void
    {
        $order       = $this->orderWithStatus(Status::CANCEL);
        $transitions = $this->pipeline->getAvailableTransitions($order);

        $this->assertEmpty($transitions);
    }

    // ----------------------------------------------------------------

    private function orderWithStatus(string $statusValue): GuestOrder
    {
        return new GuestOrder(
            id:               Uuid::random(),
            festivalId:       Uuid::random(),
            userId:           Uuid::random(),
            status:           new Status($statusValue),
            tickets:          [],
            typesOfPaymentId: Uuid::random(),
            price:            new PriceDto(3800, 1, 0),
            ticketTypeId:     Uuid::random(),
            phone:            '+79991234567',
        );
    }
}
