<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use App\Models\BazaDelivery\BazaDeliveryModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\BazaDeliveryLifecycleEvent;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Админ-API «Доставка в baza» (admin-only, ПДн): список + фильтры + пагинация, деталь + история,
 * повтор (resend), статистика для дашборда. По образцу QrOrderGetListApiTest.
 */
class BazaDeliveryApiTest extends TestCase
{
    use RefreshDatabase;

    private const F1 = '11111111-1111-1111-1111-111111111111';

    private function makeDelivery(array $overrides = []): string
    {
        $id = Uuid::random()->value();
        BazaDeliveryModel::create(array_merge([
            'id' => $id,
            'ticket_id' => Uuid::random()->value(),
            'order_id' => Uuid::random()->value(),
            'target' => 'el_tickets',
            'status' => BazaDeliveryStatus::FAILED,
            'attempts' => 2,
            'error' => 'Baza недоступна',
            'name' => 'Иван Гость',
            'email' => 'guest@example.com',
            'number' => 100,
            'festival_id' => self::F1,
            'source' => 'org_event',
        ], $overrides));

        return $id;
    }

    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    public function test_get_list_requires_authentication(): void
    {
        $this->postJson('/api/v1/bazaDelivery/getList')->assertStatus(401);
    }

    public function test_get_list_forbidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');
        $this->postJson('/api/v1/bazaDelivery/getList')->assertStatus(403);
    }

    public function test_get_list_returns_list_with_total(): void
    {
        $this->actingAsAdmin();
        $this->makeDelivery();
        $this->makeDelivery();
        $this->makeDelivery(['status' => BazaDeliveryStatus::DELIVERED]);

        $response = $this->postJson('/api/v1/bazaDelivery/getList')->assertOk()->assertJson(['success' => true]);

        $response->assertJsonPath('totalNumber.totalCount', 3);
        self::assertCount(3, $response->json('list'));
        $response->assertJsonStructure([
            'list' => [['id', 'ticket_id', 'order_id', 'target', 'status', 'attempts', 'error', 'name', 'email', 'number', 'festival_id', 'source', 'delivered_at', 'created_at']],
        ]);
    }

    public function test_get_list_filter_by_status(): void
    {
        $this->actingAsAdmin();
        $this->makeDelivery(['status' => BazaDeliveryStatus::FAILED]);
        $this->makeDelivery(['status' => BazaDeliveryStatus::FAILED]);
        $this->makeDelivery(['status' => BazaDeliveryStatus::DELIVERED]);

        $response = $this->postJson('/api/v1/bazaDelivery/getList', ['filter' => ['status' => BazaDeliveryStatus::FAILED]])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 2);
    }

    public function test_get_list_filter_by_target(): void
    {
        $this->actingAsAdmin();
        $this->makeDelivery(['target' => 'el_tickets']);
        $this->makeDelivery(['target' => 'spisok_tickets']);

        $response = $this->postJson('/api/v1/bazaDelivery/getList', ['filter' => ['target' => 'spisok_tickets']])->assertOk();

        $response->assertJsonPath('totalNumber.totalCount', 1);
    }

    public function test_get_item_returns_item_with_history(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeDelivery();
        app(HistoryRepositoryInterface::class)->save(new SaveHistoryDto(
            $id,
            new BazaDeliveryLifecycleEvent(BazaDeliveryStatus::FAILED, ['error' => 'Baza недоступна']),
            null,
            ActorType::SYSTEM,
        ));

        $response = $this->getJson('/api/v1/bazaDelivery/getItem/' . $id)->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('item.target', 'el_tickets');
        self::assertSame('baza_failed', $response->json('history.0.event_name'));
    }

    public function test_get_item_404_when_not_found(): void
    {
        $this->actingAsAdmin();
        $this->getJson('/api/v1/bazaDelivery/getItem/' . Uuid::random()->value())->assertStatus(404);
    }

    public function test_resend_requeues_failed_delivery(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        $id = $this->makeDelivery(['status' => BazaDeliveryStatus::FAILED]);

        $this->postJson('/api/v1/bazaDelivery/resend/' . $id)->assertOk()->assertJson(['success' => true]);

        self::assertSame(BazaDeliveryStatus::QUEUED, BazaDeliveryModel::whereId($id)->first()->status);
        Queue::assertPushed(DeliverTicketToBazaJob::class);
    }

    public function test_resend_404_when_not_found(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        $this->postJson('/api/v1/bazaDelivery/resend/' . Uuid::random()->value())->assertStatus(404);
    }

    public function test_get_stats_returns_status_counts(): void
    {
        $this->actingAsAdmin();
        $this->makeDelivery(['status' => BazaDeliveryStatus::FAILED, 'festival_id' => self::F1]);
        $this->makeDelivery(['status' => BazaDeliveryStatus::FAILED, 'festival_id' => self::F1]);
        $this->makeDelivery(['status' => BazaDeliveryStatus::DELIVERED, 'festival_id' => self::F1]);

        $response = $this->postJson('/api/v1/bazaDelivery/getStats')->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('stats.failed', 2);
        $response->assertJsonPath('stats.stuck', 2);
        $response->assertJsonPath('stats.delivered', 1);
    }
}
