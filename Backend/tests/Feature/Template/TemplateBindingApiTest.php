<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use App\Models\User;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * Часть B: CRUD привязок шаблонов + валидация (admin-only).
 */
class TemplateBindingApiTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function makeEmailTemplate(): string
    {
        return TemplateModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'slug' => 'orderToPaid',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Оплата',
            'body' => 'B',
            'active' => true,
            'is_system' => false,
        ])->id;
    }

    public function test_create_and_list(): void
    {
        $this->actingAsAdmin();
        $tplId = $this->makeEmailTemplate();

        $this->postJson('/api/v1/templateBinding/create', ['data' => [
            'order_type' => 'friendly',
            'email_template_id' => $tplId,
        ]])->assertStatus(200)->assertJsonPath('success', true);

        $this->postJson('/api/v1/templateBinding/getList')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'list');
    }

    public function test_create_without_template_returns_422(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/templateBinding/create', ['data' => ['order_type' => 'friendly']])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_two_active_defaults_returns_422(): void
    {
        $this->actingAsAdmin();
        $tplId = $this->makeEmailTemplate();

        $this->postJson('/api/v1/templateBinding/create', ['data' => ['is_default' => true, 'email_template_id' => $tplId]])
            ->assertStatus(200);

        $this->postJson('/api/v1/templateBinding/create', ['data' => ['is_default' => true, 'email_template_id' => $tplId]])
            ->assertStatus(422);
    }

    public function test_delete(): void
    {
        $this->actingAsAdmin();
        $tplId = $this->makeEmailTemplate();

        $id = $this->postJson('/api/v1/templateBinding/create', ['data' => ['order_type' => 'regular', 'email_template_id' => $tplId]])
            ->json('item.id');

        $this->deleteJson('/api/v1/templateBinding/delete/' . $id)
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/templateBinding/getList')->assertJsonCount(0, 'list');
    }

    public function test_wrong_kind_template_returns_422(): void
    {
        $this->actingAsAdmin();
        $pdfId = TemplateModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'slug' => 'pdf',
            'kind' => TemplateKind::PDF,
            'engine' => 'html',
            'title' => 'PDF',
            'body' => 'B',
            'active' => true,
            'is_system' => false,
        ])->id;

        // PDF-шаблон в слоте письма → 422 (кросс-проверка типа).
        $this->postJson('/api/v1/templateBinding/create', ['data' => ['order_type' => 'regular', 'email_template_id' => $pdfId]])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_requires_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');

        $this->postJson('/api/v1/templateBinding/getList')->assertStatus(403);
    }
}
