<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use App\Models\User;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * Черновик / публикация / версии / откат шаблонов (admin-only).
 */
class TemplateVersionsApiTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    private function makeTemplate(): string
    {
        $model = TemplateModel::create([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
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

    /** Найти id версии по её body (устойчиво к одинаковому created_at). */
    private function versionIdByBody(array $versions, string $body): ?string
    {
        foreach ($versions as $v) {
            if (($v['body'] ?? null) === $body) {
                return $v['id'];
            }
        }

        return null;
    }

    public function test_save_draft_does_not_touch_body(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/saveDraft/' . $id, ['draft_body' => 'ЧЕРНОВИК'])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/template/getItem/' . $id)
            ->assertJsonPath('item.body', 'INITIAL')
            ->assertJsonPath('item.draft_body', 'ЧЕРНОВИК');
    }

    public function test_publish_sets_body_and_snapshots_version(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V1', 'comment' => 'первый'])
            ->assertStatus(200)
            ->assertJsonPath('item.body', 'V1');

        $this->getJson('/api/v1/template/versions/' . $id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'versions');

        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V2'])
            ->assertJsonPath('item.body', 'V2');

        $this->getJson('/api/v1/template/versions/' . $id)
            ->assertJsonCount(2, 'versions');
    }

    public function test_rollback_restores_previous_body(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V1']);
        $this->postJson('/api/v1/template/publish/' . $id, ['body' => 'V2']);

        $versions = $this->getJson('/api/v1/template/versions/' . $id)->json('versions');
        $v1Id = $this->versionIdByBody($versions, 'V1');
        $this->assertNotNull($v1Id);

        $this->postJson('/api/v1/template/rollback/' . $id . '/' . $v1Id)
            ->assertStatus(200)
            ->assertJsonPath('item.body', 'V1');

        // Откат append-only: добавилась третья версия.
        $this->getJson('/api/v1/template/versions/' . $id)->assertJsonCount(3, 'versions');
    }

    public function test_rollback_404_for_missing_version(): void
    {
        $this->actingAsAdmin();
        $id = $this->makeTemplate();

        $this->postJson('/api/v1/template/rollback/' . $id . '/' . \Ramsey\Uuid\Uuid::uuid4()->toString())
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_versions_requires_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');

        $this->getJson('/api/v1/template/versions/' . \Ramsey\Uuid\Uuid::uuid4()->toString())
            ->assertStatus(403);
    }
}
