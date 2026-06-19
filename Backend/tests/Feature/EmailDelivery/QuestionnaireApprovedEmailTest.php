<?php

declare(strict_types=1);

namespace Tests\Feature\EmailDelivery;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\Questionnaire\Domain\DomainEvent\ProcessQuestionnaireApprovedNotification;

/**
 * Письмо «анкета одобрена» проходит через MailDispatcher: попадает в трекинг (email_messages,
 * event=questionnaire_approved, source=org_event) и ставится в очередь доставки (SendEmailJob).
 */
class QuestionnaireApprovedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_approved_email_is_tracked(): void
    {
        Queue::fake();

        (new ProcessQuestionnaireApprovedNotification('guest@example.com'))->handle();

        $this->assertDatabaseHas('email_messages', [
            'recipient' => 'guest@example.com',
            'event' => 'questionnaire_approved',
            'source' => 'org_event',
            'status' => 'queued',
        ]);
        Queue::assertPushed(SendEmailJob::class);
    }
}
