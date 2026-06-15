<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

/**
 * Слайс 3 фазы 1: рендер PDF из БД-шаблона с fallback на blade + artisan-импорт blade.
 */
class TemplatePdfAndImportTest extends TestCase
{
    private function service(): CreatingQrCodeService
    {
        return app(CreatingQrCodeService::class);
    }

    private function makeTemplate(array $overrides = []): void
    {
        TemplateModel::create(array_merge([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            'slug' => 'pdf',
            'kind' => TemplateKind::PDF,
            'engine' => 'html',
            'title' => 'Билет',
            'body' => 'Билет {{ name }} / {{ email }} / {{ kilter }}',
            'active' => true,
            'is_system' => false,
        ], $overrides));
    }

    private array $vars = ['name' => 'Аня', 'email' => 'a@b.c', 'kilter' => 7, 'url' => 'data:image/png;base64,AAA=='];

    // ─── Рендер из БД с fallback ─────────────────────────────────────────────────

    public function test_renders_from_active_db_template(): void
    {
        $this->makeTemplate();

        $html = $this->service()->resolveTemplateHtml('pdf', $this->vars);

        $this->assertSame('Билет Аня / a@b.c / 7', $html);
    }

    public function test_returns_null_when_no_template_so_falls_back_to_blade(): void
    {
        $this->assertNull($this->service()->resolveTemplateHtml('pdf', $this->vars));
    }

    public function test_returns_null_when_template_inactive(): void
    {
        $this->makeTemplate(['active' => false]);

        $this->assertNull($this->service()->resolveTemplateHtml('pdf', $this->vars));
    }

    public function test_raw_qr_url_in_pdf_template_not_escaped(): void
    {
        // QR — data-URI, должен идти raw ({{{ }}}), иначе '=' и '+' экранируются и картинка ломается.
        $this->makeTemplate(['body' => '<img src="{{{ url }}}">']);

        $html = $this->service()->resolveTemplateHtml('pdf', $this->vars);

        $this->assertStringContainsString('src="data:image/png;base64,AAA=="', $html);
    }

    // ─── Импорт blade ────────────────────────────────────────────────────────────

    public function test_import_seeds_inactive_system_templates(): void
    {
        $this->artisan('templates:import-blade')->assertSuccessful();

        // Письмо orderToPaid и PDF pdf должны появиться, оба неактивные и системные.
        $email = TemplateModel::whereSlug('orderToPaid')->whereKind(TemplateKind::EMAIL)->first();
        $this->assertNotNull($email);
        $this->assertFalse((bool) $email->active);
        $this->assertTrue((bool) $email->is_system);

        $pdf = TemplateModel::whereSlug('pdf')->whereKind(TemplateKind::PDF)->first();
        $this->assertNotNull($pdf);
        $this->assertFalse((bool) $pdf->active);

        // welcome.blade.php не должен попасть в PDF-шаблоны.
        $this->assertNull(TemplateModel::whereSlug('welcome')->first());
    }

    public function test_import_is_idempotent(): void
    {
        $this->artisan('templates:import-blade')->assertSuccessful();
        $countAfterFirst = TemplateModel::count();
        $this->assertGreaterThan(0, $countAfterFirst);

        $this->artisan('templates:import-blade')->assertSuccessful();
        $this->assertSame($countAfterFirst, TemplateModel::count());
    }

    public function test_import_does_not_overwrite_existing_admin_edit(): void
    {
        // Админ уже отредактировал и активировал orderToPaid — импорт не должен затереть.
        $this->makeTemplate([
            'slug' => 'orderToPaid',
            'kind' => TemplateKind::EMAIL,
            'body' => 'РУЧНАЯ ПРАВКА {{ order.email }}',
            'active' => true,
        ]);

        $this->artisan('templates:import-blade')->assertSuccessful();

        $row = TemplateModel::whereSlug('orderToPaid')->whereKind(TemplateKind::EMAIL)->first();
        $this->assertSame('РУЧНАЯ ПРАВКА {{ order.email }}', $row->body);
        $this->assertTrue((bool) $row->active);
    }
}
