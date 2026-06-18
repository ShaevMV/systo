<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\Festival\FestivalModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;

/**
 * AF-7: Festival — AggregateRoot + история.
 *
 * Действия create/edit/delete пишут события в domain_history (aggregate_type=festival,
 * actor_type=user). Журнал отдаётся GET /api/v1/festival/getHistory/{id} (admin).
 */
class FestivalHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeFestival(array $overrides = []): FestivalModel
    {
        return FestivalModel::create(array_merge([
            'id' => Uuid::random()->value(),
            'name' => 'Систо',
            'year' => 2026,
            'active' => true,
        ], $overrides));
    }

    private function events(string $festivalId): array
    {
        return collect(
            $this->getJson('/api/v1/festival/getHistory/' . $festivalId)
                ->assertOk()
                ->assertJson(['success' => true])
                ->json('history')
        )->pluck('event_name')->all();
    }

    public function test_create_records_festival_created(): void
    {
        $this->actingAs($this->admin(), 'api');

        $id = $this->postJson('/api/v1/festival/create', [
            'data' => ['name' => 'Систо-Осень', 'year' => 2026, 'active' => true],
        ])->assertOk()->json('item.id');

        $this->assertContains('festival_created', $this->events($id));
    }

    public function test_edit_records_changed_fields(): void
    {
        $festival = $this->makeFestival(['name' => 'Старое', 'year' => 2025, 'active' => false]);
        $this->actingAs($this->admin(), 'api');

        $this->postJson('/api/v1/festival/edit/' . $festival->id, [
            'data' => ['name' => 'Новое', 'year' => 2027, 'active' => true],
        ])->assertOk();

        $edited = collect(
            $this->getJson('/api/v1/festival/getHistory/' . $festival->id)->assertOk()->json('history')
        )->firstWhere('event_name', 'festival_edited');

        $this->assertNotNull($edited);
        $this->assertEqualsCanonicalizing(['name', 'year', 'active'], $edited['payload']['changed']);
    }

    public function test_edit_without_changes_records_no_history(): void
    {
        $festival = $this->makeFestival(['name' => 'Без изменений', 'year' => 2026, 'active' => true]);
        $this->actingAs($this->admin(), 'api');

        $this->postJson('/api/v1/festival/edit/' . $festival->id, [
            'data' => ['name' => 'Без изменений', 'year' => 2026, 'active' => true],
        ])->assertOk();

        $this->assertNotContains('festival_edited', $this->events($festival->id));
    }

    public function test_delete_records_festival_deleted(): void
    {
        $festival = $this->makeFestival();
        $this->actingAs($this->admin(), 'api');

        $this->deleteJson('/api/v1/festival/delete/' . $festival->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertContains('festival_deleted', $this->events($festival->id));
    }

    public function test_get_history_requires_admin(): void
    {
        $festival = $this->makeFestival();

        // без токена
        $this->getJson('/api/v1/festival/getHistory/' . $festival->id)->assertStatus(401);

        // не админ
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');
        $this->getJson('/api/v1/festival/getHistory/' . $festival->id)->assertStatus(403);
    }
}
