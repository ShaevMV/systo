<?php

declare(strict_types=1);

namespace Tests\Unit\BazaDelivery;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;

/**
 * Машина состояний доставки билета в Baza (без БД). Гарантирует, что «доставлено» достижимо только
 * из «отправляется», а сбой можно вернуть в очередь (авто-ретрай / ручной повтор).
 */
class BazaDeliveryStatusTest extends TestCase
{
    public function test_valid_forward_transitions(): void
    {
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::QUEUED))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::SENDING)));
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::SENDING))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::DELIVERED)));
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::QUEUED))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::FAILED)));
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::SENDING))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::FAILED)));
    }

    public function test_delivered_only_from_sending(): void
    {
        // Доставленным становится только то, что начали отправлять — не прямо из очереди или сбоя.
        $this->assertFalse((new BazaDeliveryStatus(BazaDeliveryStatus::QUEUED))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::DELIVERED)));
        $this->assertFalse((new BazaDeliveryStatus(BazaDeliveryStatus::FAILED))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::DELIVERED)));
    }

    public function test_failed_can_requeue(): void
    {
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::FAILED))->canTransitionTo(new BazaDeliveryStatus(BazaDeliveryStatus::QUEUED)));
    }

    public function test_delivered_is_terminal(): void
    {
        $delivered = new BazaDeliveryStatus(BazaDeliveryStatus::DELIVERED);
        foreach (BazaDeliveryStatus::all() as $status) {
            $this->assertFalse($delivered->canTransitionTo(new BazaDeliveryStatus($status)), "delivered → {$status} запрещён");
        }
    }

    public function test_unresolved_flag(): void
    {
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::QUEUED))->isUnresolved());
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::SENDING))->isUnresolved());
        $this->assertTrue((new BazaDeliveryStatus(BazaDeliveryStatus::FAILED))->isUnresolved());
        $this->assertFalse((new BazaDeliveryStatus(BazaDeliveryStatus::DELIVERED))->isUnresolved());
    }

    public function test_unknown_status_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BazaDeliveryStatus('teleported');
    }

    public function test_all_returns_four_statuses(): void
    {
        $this->assertSame(
            [BazaDeliveryStatus::QUEUED, BazaDeliveryStatus::SENDING, BazaDeliveryStatus::DELIVERED, BazaDeliveryStatus::FAILED],
            BazaDeliveryStatus::all(),
        );
    }
}
