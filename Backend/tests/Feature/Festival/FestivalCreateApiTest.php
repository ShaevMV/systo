<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Создание фестиваля (POST /api/v1/festival/create) — только admin (JWT).
 * Каталог фестивалей — мастер на org. Проверяем доступ, создание и валидацию.
 */
class FestivalCreateApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return ['data' => array_merge(
            ['name' => 'Осенний Систо', 'year' => 2026, 'active' => true],
            $overrides,
        )];
    }

    public function test_requires_authentication(): void
    {
        $this->postJson('/api/v1/festival/create', $this->payload())->assertStatus(401);
    }

    public function test_forbidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');

        $this->postJson('/api/v1/festival/create', $this->payload())->assertStatus(403);
    }

    public function test_admin_creates_festival(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');

        $this->postJson('/api/v1/festival/create', $this->payload())
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Фестиваль создан']);

        $this->assertDatabaseHas('festivals', [
            'name' => 'Осенний Систо',
            'year' => 2026,
            'active' => 1,
        ]);
    }

    public function test_validation_requires_name_and_year(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');

        // Нет name/year → 422 (защита от дурака).
        $this->postJson('/api/v1/festival/create', ['data' => ['active' => true]])
            ->assertStatus(422);
    }
}
