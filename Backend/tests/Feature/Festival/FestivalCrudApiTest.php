<?php

declare(strict_types=1);

namespace Tests\Feature\Festival;

use App\Models\Festival\FestivalModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;

/**
 * Полный CRUD каталога фестивалей (по образцу Location):
 *  - POST   /api/v1/festival/getList     — публичный, фильтры name/year/active + orderBy
 *  - GET    /api/v1/festival/getItem/{id} — публичный
 *  - POST   /api/v1/festival/edit/{id}    — admin
 *  - DELETE /api/v1/festival/delete/{id}  — admin, soft delete
 *
 * Создание (POST /create) проверяется отдельно в FestivalCreateApiTest.
 *
 * ВАЖНО: TestCase сидит DatabaseSeeder (FestivalSeeder и пр.), поэтому в БД уже
 * есть фестивали. Проверки построены на уникальных маркерах в имени и фильтрах,
 * а не на абсолютном количестве записей.
 */
class FestivalCrudApiTest extends TestCase
{
    use RefreshDatabase;

    /** Уникальный токен, которого заведомо нет в сидерах. */
    private const TOKEN = 'ЦЦЦ-КРУД-ТЕСТ';

    private function makeFestival(array $overrides = []): FestivalModel
    {
        return FestivalModel::create(array_merge([
            'id' => Uuid::random()->value(),
            'name' => self::TOKEN . ' Летний Систо',
            'year' => 2026,
            'active' => true,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ----- getList (публичный) -----

    public function test_get_list_returns_created_festivals(): void
    {
        $this->makeFestival(['name' => self::TOKEN . ' Летний Систо']);
        $this->makeFestival(['name' => self::TOKEN . ' Осенний Систо']);

        $this->postJson('/api/v1/festival/getList', [])
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonFragment(['name' => self::TOKEN . ' Летний Систо'])
            ->assertJsonFragment(['name' => self::TOKEN . ' Осенний Систо']);
    }

    public function test_get_list_filters_by_name(): void
    {
        $this->makeFestival(['name' => self::TOKEN . ' Летний Систо']);
        $this->makeFestival(['name' => self::TOKEN . ' Осенний Систо']);

        // фильтр по уникальному токену изолирует от засеянных фестивалей
        $this->postJson('/api/v1/festival/getList', ['filter' => ['name' => self::TOKEN]])
            ->assertOk()
            ->assertJsonCount(2, 'list');

        $response = $this->postJson('/api/v1/festival/getList', ['filter' => ['name' => self::TOKEN . ' Осен']])
            ->assertOk()
            ->assertJsonCount(1, 'list');

        $this->assertSame(self::TOKEN . ' Осенний Систо', $response->json('list.0.name'));
    }

    public function test_get_list_bad_order_by_does_not_crash(): void
    {
        $this->makeFestival();

        $this->postJson('/api/v1/festival/getList', ['orderBy' => ['name' => 'НЕ_ВЕРНО']])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    // ----- getItem (публичный) -----

    public function test_get_item_returns_festival(): void
    {
        $festival = $this->makeFestival(['name' => self::TOKEN . ' Летний Систо', 'year' => 2026]);

        $this->getJson('/api/v1/festival/getItem/' . $festival->id)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'item' => ['name' => self::TOKEN . ' Летний Систо', 'year' => 2026],
            ]);
    }

    public function test_get_item_not_found(): void
    {
        $this->getJson('/api/v1/festival/getItem/' . Uuid::random()->value())
            ->assertOk()
            ->assertJson(['success' => false]);
    }

    // ----- edit (admin) -----

    public function test_edit_requires_authentication(): void
    {
        $festival = $this->makeFestival();

        $this->postJson('/api/v1/festival/edit/' . $festival->id, [
            'data' => ['name' => 'X', 'year' => 2026],
        ])->assertStatus(401);
    }

    public function test_edit_forbidden_for_non_admin(): void
    {
        $festival = $this->makeFestival();
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');

        $this->postJson('/api/v1/festival/edit/' . $festival->id, [
            'data' => ['name' => 'X', 'year' => 2026],
        ])->assertStatus(403);
    }

    public function test_admin_edits_festival(): void
    {
        $festival = $this->makeFestival(['name' => self::TOKEN . ' Старое', 'year' => 2025, 'active' => false]);
        $this->actingAs($this->admin(), 'api');

        $this->postJson('/api/v1/festival/edit/' . $festival->id, [
            'data' => ['name' => self::TOKEN . ' Новое имя', 'year' => 2027, 'active' => true],
        ])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'Фестиваль отредактирован']);

        $this->assertDatabaseHas('festivals', [
            'id' => $festival->id,
            'name' => self::TOKEN . ' Новое имя',
            'year' => 2027,
            'active' => 1,
        ]);
    }

    public function test_edit_validation_requires_name_and_year(): void
    {
        $festival = $this->makeFestival();
        $this->actingAs($this->admin(), 'api');

        $this->postJson('/api/v1/festival/edit/' . $festival->id, ['data' => ['active' => true]])
            ->assertStatus(422);
    }

    // ----- delete (admin, soft delete) -----

    public function test_delete_requires_authentication(): void
    {
        $festival = $this->makeFestival();

        $this->deleteJson('/api/v1/festival/delete/' . $festival->id)->assertStatus(401);
    }

    public function test_delete_forbidden_for_non_admin(): void
    {
        $festival = $this->makeFestival();
        $this->actingAs(User::factory()->create(['role' => 'guest']), 'api');

        $this->deleteJson('/api/v1/festival/delete/' . $festival->id)->assertStatus(403);
    }

    public function test_admin_soft_deletes_festival(): void
    {
        $festival = $this->makeFestival(['name' => self::TOKEN . ' Удаляемый']);
        $this->actingAs($this->admin(), 'api');

        $this->deleteJson('/api/v1/festival/delete/' . $festival->id)
            ->assertOk()
            ->assertJson(['success' => true]);

        // soft delete: строка остаётся, но помечена удалённой
        $this->assertSoftDeleted('festivals', ['id' => $festival->id]);

        // и больше не отдаётся в списке (фильтруем по уникальному токену)
        $this->postJson('/api/v1/festival/getList', ['filter' => ['name' => self::TOKEN]])
            ->assertOk()
            ->assertJsonCount(0, 'list');
    }
}
