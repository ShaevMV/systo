<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use App\Models\User;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * Часть A: интеграция истории. Admin-действия над шаблоном пишут события в domain_history
 * (aggregate_type=template, actor_type=user). Эндпоинт /template/history/{id} отдаёт журнал.
 */
class TemplateHistoryApiTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function makeTemplate(): string
    {
        $model = TemplateModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'slug' => 'orderToPaid',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Оплата',
            'body' => 'INITIAL',
            'active' => true,
            'is_system' => false,
        ]);

        return $model->id;
    }

    public function test_publish_writes_history_row(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V1', 'comment' => 'первый'])
            ->assertStatus(200);

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id,
            'aggregate_type' => 'template',
            'event_name' => 'template_published',
            'actor_type' => 'user',
        ]);
    }

    public function test_activate_writes_history_row(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/activate/' . $id, ['active' => false])->assertStatus(200);

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id,
            'event_name' => 'template_activated',
            'actor_type' => 'user',
        ]);
    }

    public function test_create_writes_history_row(): void
    {
        $this->actingAsAdmin();
        $id = RamseyUuid::uuid4()->toString();

        $this->postJson('/api/v1/template/create', ['data' => [
            'id' => $id,
            'slug' => 'orderToCancel',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Отмена',
            'body' => 'BODY',
        ]])->assertStatus(200);

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id,
            'event_name' => 'template_created',
        ]);
    }

    public function test_edit_writes_changed_and_noop_skips(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        // Реальное изменение title → одно событие template_edited.
        $this->postJson('/api/v1/template/edit/' . $id, ['data' => [
            'id' => $id, 'slug' => 'orderToPaid', 'kind' => TemplateKind::EMAIL,
            'engine' => 'html', 'title' => 'Оплата НОВАЯ', 'body' => 'INITIAL',
        ]])->assertStatus(200);

        $this->assertDatabaseHas('domain_history', [
            'aggregate_id' => $id,
            'event_name' => 'template_edited',
        ]);
        $this->assertSame(1, \DB::table('domain_history')->where('aggregate_id', $id)->count());

        // Повтор тех же данных → изменений нет → новых записей истории нет.
        $this->postJson('/api/v1/template/edit/' . $id, ['data' => [
            'id' => $id, 'slug' => 'orderToPaid', 'kind' => TemplateKind::EMAIL,
            'engine' => 'html', 'title' => 'Оплата НОВАЯ', 'body' => 'INITIAL',
        ]])->assertStatus(200);

        $this->assertSame(1, \DB::table('domain_history')->where('aggregate_id', $id)->count());
    }

    public function test_save_draft_writes_no_history(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/saveDraft/' . $id, ['draft_body' => 'ЧЕРНОВИК'])->assertStatus(200);

        $this->assertDatabaseMissing('domain_history', ['aggregate_id' => $id]);
    }

    public function test_get_history_returns_events(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V1']);
        $this->postJson('/api/v1/template/activate/' . $id, ['active' => false]);

        $response = $this->getJson('/api/v1/template/history/' . $id)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $events = array_column($response->json('history'), 'event_name');
        $this->assertContains('template_published', $events);
        $this->assertContains('template_activated', $events);
        // Все события — типа template, actor_type=user.
        foreach ($response->json('history') as $row) {
            $this->assertSame('template', $row['aggregate_type']);
            $this->assertSame('user', $row['actor_type']);
        }
    }

    public function test_get_history_requires_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');

        $this->getJson('/api/v1/template/history/' . RamseyUuid::uuid4()->toString())
            ->assertStatus(403);
    }
}
