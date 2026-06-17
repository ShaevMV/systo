<?php

declare(strict_types=1);

namespace Tests\Feature\EmailDelivery;

use InvalidArgumentException;
use Tests\TestCase;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;

/**
 * Ф2: машина состояний письма (без БД). Гарантирует, что «прочитано» достижимо только из
 * отправленного/доставленного, а сбой/отскок можно вернуть в очередь (повтор).
 */
class EmailStatusTest extends TestCase
{
    public function test_valid_forward_transitions(): void
    {
        $this->assertTrue((new EmailStatus(EmailStatus::QUEUED))->canTransitionTo(new EmailStatus(EmailStatus::SENDING)));
        $this->assertTrue((new EmailStatus(EmailStatus::SENDING))->canTransitionTo(new EmailStatus(EmailStatus::SENT)));
        $this->assertTrue((new EmailStatus(EmailStatus::SENT))->canTransitionTo(new EmailStatus(EmailStatus::OPENED)));
        $this->assertTrue((new EmailStatus(EmailStatus::SENT))->canTransitionTo(new EmailStatus(EmailStatus::DELIVERED)));
        $this->assertTrue((new EmailStatus(EmailStatus::DELIVERED))->canTransitionTo(new EmailStatus(EmailStatus::OPENED)));
    }

    public function test_opened_only_from_sent_or_delivered(): void
    {
        // Открыть можно лишь отправленное/доставленное — не из очереди и не из сбоя.
        $this->assertFalse((new EmailStatus(EmailStatus::QUEUED))->canTransitionTo(new EmailStatus(EmailStatus::OPENED)));
        $this->assertFalse((new EmailStatus(EmailStatus::FAILED))->canTransitionTo(new EmailStatus(EmailStatus::OPENED)));
    }

    public function test_failed_and_bounced_can_requeue(): void
    {
        $this->assertTrue((new EmailStatus(EmailStatus::FAILED))->canTransitionTo(new EmailStatus(EmailStatus::QUEUED)));
        $this->assertTrue((new EmailStatus(EmailStatus::BOUNCED))->canTransitionTo(new EmailStatus(EmailStatus::QUEUED)));
    }

    public function test_opened_is_terminal(): void
    {
        $opened = new EmailStatus(EmailStatus::OPENED);
        foreach (EmailStatus::all() as $status) {
            $this->assertFalse($opened->canTransitionTo(new EmailStatus($status)), "opened → {$status} запрещён");
        }
    }

    public function test_unresolved_flag(): void
    {
        $this->assertTrue((new EmailStatus(EmailStatus::QUEUED))->isUnresolved());
        $this->assertTrue((new EmailStatus(EmailStatus::FAILED))->isUnresolved());
        $this->assertFalse((new EmailStatus(EmailStatus::SENT))->isUnresolved());
        $this->assertFalse((new EmailStatus(EmailStatus::OPENED))->isUnresolved());
    }

    public function test_unknown_status_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EmailStatus('teleported');
    }
}
