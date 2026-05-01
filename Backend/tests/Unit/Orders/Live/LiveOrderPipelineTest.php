<?php

declare(strict_types=1);

namespace Tests\Unit\Orders\Live;

use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Live\Domain\LiveOrder;
use Tickets\Orders\Live\Pipeline\LiveOrderPipeline;

/**
 * Тесты матрицы переходов LiveOrderPipeline.
 *
 * Живой заказ имеет свою уникальную матрицу:
 *   NEW_FOR_LIVE → PAID_FOR_LIVE | CANCEL | DIFFICULTIES_AROSE | LIVE_TICKET_ISSUED
 *   PAID_FOR_LIVE → LIVE_TICKET_ISSUED | CANCEL_FOR_LIVE
 *   LIVE_TICKET_ISSUED → CANCEL_FOR_LIVE
 *   CANCEL и CANCEL_FOR_LIVE — терминальные
 */
class LiveOrderPipelineTest extends TestCase
{
    private LiveOrderPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = new LiveOrderPipeline();
    }

    // ----------------------------------------------------------------
    // NEW_FOR_LIVE переходы
    // ----------------------------------------------------------------

    /** @test */
    public function new_for_live_can_transition_to_paid_for_live(): void
    {
        $order = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::PAID_FOR_LIVE)));
    }

    /** @test */
    public function new_for_live_can_transition_to_cancel(): void
    {
        $order = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::CANCEL)));
    }

    /** @test */
    public function new_for_live_can_transition_to_difficulties_arose(): void
    {
        $order = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::DIFFICULTIES_AROSE)));
    }

    /** @test */
    public function new_for_live_can_transition_directly_to_live_ticket_issued(): void
    {
        $order = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::LIVE_TICKET_ISSUED)));
    }

    // ----------------------------------------------------------------
    // PAID_FOR_LIVE переходы
    // ----------------------------------------------------------------

    /** @test */
    public function paid_for_live_can_transition_to_live_ticket_issued(): void
    {
        $order = $this->orderWithStatus(Status::PAID_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::LIVE_TICKET_ISSUED)));
    }

    /** @test */
    public function paid_for_live_can_transition_to_cancel_for_live(): void
    {
        $order = $this->orderWithStatus(Status::PAID_FOR_LIVE);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::CANCEL_FOR_LIVE)));
    }

    /** @test */
    public function paid_for_live_cannot_transition_to_new_for_live(): void
    {
        $order = $this->orderWithStatus(Status::PAID_FOR_LIVE);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW_FOR_LIVE)));
    }

    // ----------------------------------------------------------------
    // LIVE_TICKET_ISSUED переходы
    // ----------------------------------------------------------------

    /** @test */
    public function live_ticket_issued_can_transition_to_cancel_for_live(): void
    {
        $order = $this->orderWithStatus(Status::LIVE_TICKET_ISSUED);
        $this->assertTrue($this->pipeline->canTransition($order, new Status(Status::CANCEL_FOR_LIVE)));
    }

    /** @test */
    public function live_ticket_issued_cannot_transition_to_paid_for_live(): void
    {
        $order = $this->orderWithStatus(Status::LIVE_TICKET_ISSUED);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID_FOR_LIVE)));
    }

    // ----------------------------------------------------------------
    // Терминальные статусы
    // ----------------------------------------------------------------

    /** @test */
    public function cancel_is_terminal_no_transitions_allowed(): void
    {
        $order = $this->orderWithStatus(Status::CANCEL);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID_FOR_LIVE)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW_FOR_LIVE)));
    }

    /** @test */
    public function cancel_for_live_is_terminal(): void
    {
        $order = $this->orderWithStatus(Status::CANCEL_FOR_LIVE);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID_FOR_LIVE)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::LIVE_TICKET_ISSUED)));
    }

    // ----------------------------------------------------------------
    // Недопустимые переходы (не перепутать guest и live)
    // ----------------------------------------------------------------

    /** @test */
    public function guest_statuses_not_allowed_in_live_pipeline(): void
    {
        $order = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::NEW)));
        $this->assertFalse($this->pipeline->canTransition($order, new Status(Status::PAID)));
    }

    // ----------------------------------------------------------------
    // Доступные переходы
    // ----------------------------------------------------------------

    /** @test */
    public function new_for_live_returns_four_available_transitions(): void
    {
        $order       = $this->orderWithStatus(Status::NEW_FOR_LIVE);
        $transitions = $this->pipeline->getAvailableTransitions($order);

        $this->assertCount(4, $transitions);
    }

    /** @test */
    public function cancel_for_live_returns_no_available_transitions(): void
    {
        $order       = $this->orderWithStatus(Status::CANCEL_FOR_LIVE);
        $transitions = $this->pipeline->getAvailableTransitions($order);

        $this->assertEmpty($transitions);
    }

    // ----------------------------------------------------------------

    private function orderWithStatus(string $statusValue): LiveOrder
    {
        return new LiveOrder(
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
