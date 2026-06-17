<?php

declare(strict_types=1);

namespace Tests\Feature\EmailDelivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationListCancel;
use Tickets\Order\OrderTicket\Domain\ProcessUserNotificationOrderCancel;
use Tickets\User\Account\Domain\ProcessAccountNotification;

/**
 * Legacy org-письма (отмена/изменение/регистрация и пр.) после перевода на MailDispatcher
 * попадают в трекинг (email_messages, source=org_event) — больше не идут мимо диспетчера.
 */
class LegacyOrgEmailsTrackedTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_cancel_email_is_tracked(): void
    {
        Queue::fake();

        (new ProcessUserNotificationOrderCancel('guest@example.com', Uuid::random()))->handle();

        $this->assertDatabaseHas('email_messages', [
            'recipient' => 'guest@example.com',
            'event' => 'order_cancel',
            'source' => 'org_event',
            'status' => 'queued',
        ]);
        Queue::assertPushed(SendEmailJob::class);
    }

    public function test_list_cancel_email_is_tracked(): void
    {
        Queue::fake();

        (new ProcessUserNotificationListCancel('recipient@example.com', Uuid::random()))->handle();

        $this->assertDatabaseHas('email_messages', [
            'recipient' => 'recipient@example.com',
            'event' => 'list_cancel',
            'source' => 'org_event',
        ]);
    }

    public function test_account_registration_email_is_tracked(): void
    {
        Queue::fake();

        (new ProcessAccountNotification('newuser@example.com', 'secret-pass'))->handle();

        $this->assertDatabaseHas('email_messages', [
            'recipient' => 'newuser@example.com',
            'event' => 'user_registered',
            'source' => 'org_event',
        ]);
    }
}
