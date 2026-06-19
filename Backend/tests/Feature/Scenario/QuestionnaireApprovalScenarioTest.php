<?php

declare(strict_types=1);

namespace Tests\Feature\Scenario;

use App\Models\Questionnaire\QuestionnaireModel;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Questionnaire\Application\Questionnaire\QuestionnaireApplication;
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;

/**
 * BDD-сценарий одобрения анкеты гостя администратором.
 *
 * Запуск (через Docker):
 *   docker exec php-solarSysto ./vendor/bin/phpunit --filter QuestionnaireApprovalScenarioTest --testdox
 *
 * Письмо «анкета одобрена» проверяется отдельно (QuestionnaireApprovedEmailTest на уровне
 * доменного события); здесь — синхронные эффекты одобрения: статус анкеты и запись в историю.
 */
class QuestionnaireApprovalScenarioTest extends TestCase
{
    private const QUESTIONNAIRE_ID = 4242;

    public function test_scenario_admin_approves_questionnaire(): void
    {
        Queue::fake();

        // Дано: анкета гостя в статусе NEW (ожидает проверки).
        QuestionnaireModel::create([
            'id' => self::QUESTIONNAIRE_ID,
            'email' => 'guest@example.com',
            'order_id' => '11111111-1111-4111-8111-111111111111',
            'ticket_id' => '22222222-2222-4222-8222-222222222222',
            'festival_id' => FestivalHelper::UUID_FESTIVAL,
            'status' => QuestionnaireStatus::NEW,
            'data' => [],
        ]);

        // Когда: администратор одобряет анкету.
        app(QuestionnaireApplication::class)->approve(self::QUESTIONNAIRE_ID);

        // Тогда: статус анкеты стал APPROVE.
        $this->assertDatabaseHas('questionnaire', [
            'id' => self::QUESTIONNAIRE_ID,
            'status' => QuestionnaireStatus::APPROVE,
        ]);

        // И: факт одобрения записан в журнал истории (aggregate_type = questionnaire).
        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => (string) self::QUESTIONNAIRE_ID,
            'aggregate_type' => 'questionnaire',
            'event_name' => 'questionnaire_approved',
        ]);
    }
}
