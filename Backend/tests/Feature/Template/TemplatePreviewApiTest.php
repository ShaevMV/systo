<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\User;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * Предпросмотр шаблона (/api/v1/template/preview) — рендер на тестовых данных, admin-only.
 */
class TemplatePreviewApiTest extends TestCase
{
    private function actingAsAdmin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']), 'api');
    }

    public function test_requires_auth(): void
    {
        $this->postJson('/api/v1/template/preview')->assertStatus(401);
    }

    public function test_forbidden_for_non_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'seller']), 'api');
        $this->postJson('/api/v1/template/preview', ['kind' => 'email', 'body' => 'x'])->assertStatus(403);
    }

    public function test_email_preview_renders_html_on_sample_data(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/template/preview', [
            'kind' => TemplateKind::EMAIL,
            'slug' => 'orderToPaid',
            'body' => 'Фестиваль {{ festivalName }}, гости: {{#guests}}{{name}};{{/guests}}',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('html', 'Фестиваль Солар Систо 2026, гости: Иван Иванов;Мария Петрова;');
    }

    public function test_email_preview_escapes_php_in_body(): void
    {
        $this->actingAsAdmin();

        $html = $this->postJson('/api/v1/template/preview', [
            'kind' => TemplateKind::EMAIL,
            'body' => '{{ name }} <?php system("x"); ?>',
        ])->assertStatus(200)->json('html');

        // Литеральный <?php в теле — не исполняется (Mustache его не компилирует).
        $this->assertStringContainsString('Иван Иванов', $html);
    }

    public function test_pdf_preview_returns_pdf_stream(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/template/preview', [
            'kind' => TemplateKind::PDF,
            'slug' => 'pdf',
            'body' => '<h1>Билет {{ name }}</h1><img src="{{{ url }}}">',
        ]);

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_malformed_template_returns_422(): void
    {
        $this->actingAsAdmin();

        // Незакрытая секция {{#guests}} → Mustache бросает синтаксическую ошибку → 422, не 500.
        $this->postJson('/api/v1/template/preview', [
            'kind' => TemplateKind::EMAIL,
            'body' => 'Гости: {{#guests}}{{ name }}',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
