<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

/**
 * Фаза 5: команда templates:sync-converted загружает конвертированные Mustache-шаблоны и активирует,
 * после чего PDF-билет рендерится из БД.
 */
class TemplateSyncCommandTest extends TestCase
{
    public function test_sync_activates_converted_pdf_and_it_renders(): void
    {
        $this->artisan('templates:sync-converted')->assertSuccessful();

        // 'pdf' создан/обновлён из resources/views/mustache/pdf.pdf.mustache и активен.
        $pdf = TemplateModel::whereSlug('pdf')->whereKind(TemplateKind::PDF)->first();
        $this->assertNotNull($pdf);
        $this->assertTrue((bool) $pdf->active);
        $this->assertStringContainsString('Solar Systo', $pdf->body);
        // Mustache-синтаксис (не blade).
        $this->assertStringContainsString('{{ name }}', $pdf->body);
        $this->assertStringNotContainsString('{{$name}}', $pdf->body);

        // Рендер из БД через реальный сервис билетов.
        $html = app(CreatingQrCodeService::class)->resolveTemplateHtml('pdf', [
            'name' => 'Аня Тест',
            'email' => 'anya@example.com',
            'kilter' => 77,
            'url' => 'data:image/png;base64,AAA==',
            'year' => '2026'
        ]);

        $this->assertStringContainsString('Имя: Аня Тест', $html);
        $this->assertStringContainsString('Email: anya@example.com', $html);
        $this->assertStringContainsString('ID: E-77', $html);
        $this->assertStringContainsString('2026', $html);
        // QR data-URI идёт raw — не экранирован.
        $this->assertStringContainsString('src="data:image/png;base64,AAA=="', $html);
    }

    public function test_sync_is_idempotent(): void
    {
        $this->artisan('templates:sync-converted')->assertSuccessful();
        $countAfterFirst = TemplateModel::whereSlug('pdf')->count();

        $this->artisan('templates:sync-converted')->assertSuccessful();
        $this->assertSame($countAfterFirst, TemplateModel::whereSlug('pdf')->count());
    }
}
