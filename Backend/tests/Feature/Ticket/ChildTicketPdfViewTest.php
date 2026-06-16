<?php

declare(strict_types=1);

namespace Tests\Feature\Ticket;

use Tests\TestCase;

/**
 * Регресс: детский PDF-шаблон TypeTicketPdfChild должен СУЩЕСТВОВАТЬ и рендериться.
 *
 * Детский тип билета (миграция 2026_04_05_110000) ссылается на вью TypeTicketPdfChild,
 * но blade-файла не было → Pdf::loadView падал «View not found» и ронял ВСЁ письмо заказа
 * со смешанными типами (доходило только парковочное «эко-сбор»). Pipeline-тесты это не ловили,
 * т.к. Mail::fake не вызывает build() мейлабла (рендер PDF только при реальной отправке).
 */
class ChildTicketPdfViewTest extends TestCase
{
    public function test_child_pdf_view_exists(): void
    {
        self::assertTrue(view()->exists('TypeTicketPdfChild'), 'Вью TypeTicketPdfChild должен существовать');
    }

    public function test_child_pdf_view_renders_with_ticket_vars(): void
    {
        // Переменные — те же, что отдаёт CreatingQrCodeService::createPdf.
        $html = view('TypeTicketPdfChild', [
            'url' => 'data:image/png;base64,AAAA',
            'name' => 'Петрова Аня (4 года)',
            'email' => 'kid@example.com',
            'kilter' => 42,
            'year' => 2026,
        ])->render();

        self::assertStringContainsString('Петрова Аня (4 года)', $html);
        self::assertStringContainsString('kid@example.com', $html);
        self::assertStringContainsString('E-42', $html);
    }
}
