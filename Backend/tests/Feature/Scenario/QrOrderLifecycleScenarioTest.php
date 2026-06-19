<?php

declare(strict_types=1);

namespace Tests\Feature\Scenario;

use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\QrOrder\WithQrIngestToken;
use Tests\TestCase;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\QrOrder\Application\Issuance\IssueOrderJob;

/**
 * BDD-сценарии полного жизненного цикла qr-заказа (читается как живая документация).
 *
 * Запуск (через Docker, как все тесты проекта):
 *   docker exec php-solarSysto ./vendor/bin/phpunit --filter QrOrderLifecycleScenarioTest --testdox
 *
 * Покрывает оба режима приёма заказа от витрины qr.spaceofjoy.ru:
 *  - двухшаговый: «создан» → письмо «заказ создан» → «оплачен» → выдача билетов;
 *  - одношаговый: сразу «оплачен» → немедленная выдача.
 */
class QrOrderLifecycleScenarioTest extends TestCase
{
    use WithQrIngestToken;

    private const ORDER_ID = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureQrIngestToken();
    }

    /** Контракт qr-заказа с заданным статусом (обычный заказ, один гость). */
    private function contract(string $status): array
    {
        return [
            'order_id' => self::ORDER_ID,
            'external_order_no' => 49,
            'user' => ['name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['total' => 4200],
            'order_data' => [
                'type_order' => 'regular',
                'festival' => ['id' => FestivalHelper::UUID_FESTIVAL, 'title' => 'Систо'],
                'status' => $status,
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com',
                 'type_ticket' => ['id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE, 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    /**
     * Сценарий: двухшаговый цикл «создан → оплачен».
     */
    public function test_scenario_two_step_create_then_pay(): void
    {
        Queue::fake();

        // Дано: витрина qr с валидным сервисным ключом X-QR-Token.
        // Когда: витрина присылает заказ в статусе «создан».
        $this->postJson('/api/v1/qrOrder/create', $this->contract('создан'), $this->qrIngestHeaders())
            ->assertOk();

        // Тогда: org поставил письмо «заказ создан» (order_created) и НЕ выпустил билеты.
        $this->assertDatabaseHas('email_messages', [
            'aggregate_id' => self::ORDER_ID,
            'event' => 'order_created',
            'source' => 'qr_pipeline',
        ]);
        Queue::assertNotPushed(IssueOrderJob::class);
        $this->assertDatabaseHas('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);

        // Когда: гость оплатил на витрине — qr переводит заказ в «оплачен».
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'], $this->qrIngestHeaders())
            ->assertOk();

        // Тогда: org запустил выдачу билетов (один раз) и пометил заказ выданным.
        Queue::assertPushed(IssueOrderJob::class, 1);
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    /**
     * Сценарий: одношаговый приём — заказ приходит уже оплаченным.
     */
    public function test_scenario_one_step_paid_on_arrival(): void
    {
        Queue::fake();

        // Когда: витрина присылает заказ сразу в статусе «оплачен».
        $this->postJson('/api/v1/qrOrder/create', $this->contract('оплачен'), $this->qrIngestHeaders())
            ->assertOk();

        // Тогда: org сразу запустил выдачу билетов; письма «заказ создан» при этом НЕТ.
        Queue::assertPushed(IssueOrderJob::class, 1);
        $this->assertDatabaseMissing('email_messages', [
            'aggregate_id' => self::ORDER_ID,
            'event' => 'order_created',
        ]);
        $this->assertDatabaseMissing('qr_orders', ['id' => self::ORDER_ID, 'issued_at' => null]);
    }

    /**
     * Сценарий: повторный «оплачен» (ретрай qr) не выпускает билеты второй раз.
     */
    public function test_scenario_repeated_paid_is_idempotent(): void
    {
        Queue::fake();

        // Дано: заказ уже принят оплаченным (выдача запущена один раз).
        $this->postJson('/api/v1/qrOrder/create', $this->contract('оплачен'), $this->qrIngestHeaders())->assertOk();

        // Когда: витрина повторно шлёт «оплачен» (сетевой ретрай / дубль вебхука).
        $this->postJson('/api/v1/qrOrder/changeStatus/' . self::ORDER_ID, ['status' => 'оплачен'], $this->qrIngestHeaders())
            ->assertOk();

        // Тогда: выдача НЕ запускается повторно (защита по issued_at).
        Queue::assertPushed(IssueOrderJob::class, 1);
    }
}
