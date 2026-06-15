<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\User;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * CRUD-API шаблонов (/api/v1/template/*) — admin-only.
 */
class TemplateApiTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function createPayload(array $overrides = []): array
    {
        return ['data' => array_merge([
            'slug' => 'orderToPaid',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Оплата заказа',
            'body' => 'Привет, {{ order.email }}',
            'active' => true,
        ], $overrides)];
    }

    public function test_get_list_requires_auth(): void
    {
        $this->postJson('/api/v1/template/getList')->assertStatus(401);
    }

    public function test_forbidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');
        $this->postJson('/api/v1/template/getList')->assertStatus(403);
    }

    public function test_create_and_get_item(): void
    {
        $this->actingAsAdmin();

        $created = $this->postJson('/api/v1/template/create', $this->createPayload())
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->json('item');

        $this->assertSame('orderToPaid', $created['slug']);

        $this->getJson('/api/v1/template/getItem/' . $created['id'])
            ->assertStatus(200)
            ->assertJsonPath('item.slug', 'orderToPaid')
            ->assertJsonPath('item.body', 'Привет, {{ order.email }}');
    }

    public function test_get_list_returns_and_filters(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/template/create', $this->createPayload(['slug' => 'orderToPaid', 'kind' => TemplateKind::EMAIL]))->assertStatus(200);
        $this->postJson('/api/v1/template/create', $this->createPayload(['slug' => 'pdf', 'kind' => TemplateKind::PDF]))->assertStatus(200);

        $this->postJson('/api/v1/template/getList')
            ->assertStatus(200)
            ->assertJsonCount(2, 'list');

        $this->postJson('/api/v1/template/getList', ['filter' => ['kind' => TemplateKind::PDF]])
            ->assertStatus(200)
            ->assertJsonCount(1, 'list')
            ->assertJsonPath('list.0.slug', 'pdf');
    }

    public function test_edit_updates_body(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/api/v1/template/create', $this->createPayload())->json('item.id');

        $this->postJson('/api/v1/template/edit/' . $id, $this->createPayload(['body' => 'Новое тело {{ name }}']))
            ->assertStatus(200)
            ->assertJsonPath('item.body', 'Новое тело {{ name }}');
    }

    public function test_activate_toggles(): void
    {
        $this->actingAsAdmin();

        $id = $this->postJson('/api/v1/template/create', $this->createPayload(['active' => true]))->json('item.id');

        $this->postJson('/api/v1/template/activate/' . $id, ['active' => false])
            ->assertStatus(200)
            ->assertJsonPath('item.active', false);

        $this->postJson('/api/v1/template/activate/' . $id, ['active' => true])
            ->assertStatus(200)
            ->assertJsonPath('item.active', true);
    }

    public function test_get_item_404_for_missing(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/template/getItem/' . \Ramsey\Uuid\Uuid::uuid4()->toString())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
