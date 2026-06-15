<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use Tests\TestCase;
use Tickets\Template\Service\TemplateRenderer;

/**
 * Фаза 5 (конвертация blade→Mustache): ГЛУБОКАЯ проверка, что КАЖДЫЙ конвертированный
 * шаблон из resources/views/mustache/ реально рендерится с боевыми данными.
 *
 * Проверяем не «поверхность» (компилируется ли), а семантику:
 *  - все шаблоны рендерятся без падений и без НЕразрешённых тегов ({{ … }} не остаётся);
 *  - секции ({{# x }}) РАСКРЫВАЮТСЯ при наличии данных И СХЛОПЫВАЮТСЯ без них;
 *  - {{ var }} экранирует HTML, {{{ url }}} (QR data-URI) идёт raw;
 *  - {{ year }} подставляется.
 */
class TemplateConversionRenderTest extends TestCase
{
    private function richContext(): array
    {
        return [
            'year' => '2026',
            'festivalName' => 'Solar Systo 2026',
            'comment' => 'Тестовый комментарий',
            'promocode' => 'TRANSFER2026',
            'kilter' => 777,
            'name' => 'Аня Тест',
            'email' => 'anya@example.com',
            'url' => 'data:image/png;base64,AAA==',
            'locationName' => 'Главная поляна',
            'changes' => [
                ['oldName' => 'Старое Имя', 'newName' => 'Новое Имя'],
            ],
            'questionnaireLinks' => [
                ['name' => 'Гость Один', 'url' => 'https://q.example/1'],
                ['name' => 'Гость Два', 'url' => 'https://q.example/2'],
            ],
        ];
    }

    /** Все конвертированные шаблоны рендерятся без падений и без неразрешённых тегов. */
    public function test_all_converted_templates_render_clean(): void
    {
        $renderer = app(TemplateRenderer::class);
        $files = glob(resource_path('views/mustache/*.mustache'));

        $this->assertNotEmpty($files, 'Каталог конвертированных шаблонов пуст');
        $this->assertGreaterThanOrEqual(28, count($files), 'Ожидалось >= 28 шаблонов (1 база pdf + 7 PDF + 20 писем)');

        foreach ($files as $file) {
            $name = basename($file);
            $html = $renderer->render(file_get_contents($file), $this->richContext());

            $this->assertNotSame('', trim($html), "Пустой рендер: {$name}");
            // Mustache убирает все валидные {{...}}. Остался {{ — значит малформед.
            $this->assertStringNotContainsString('{{', $html, "Неразрешённые Mustache-теги в {$name}");
            // Blade-конструкции не должны просочиться в вывод.
            $this->assertStringNotContainsString('@foreach', $html, "Остаток blade @foreach в {$name}");
            $this->assertStringNotContainsString('@if', $html, "Остаток blade @if в {$name}");
        }
    }

    /** Секции раскрываются при наличии данных (циклы и условия). */
    public function test_sections_expand_with_data(): void
    {
        $renderer = app(TemplateRenderer::class);
        $ctx = $this->richContext();

        // @foreach($changes) → {{# changes }}
        $html = $renderer->render($this->tpl('orderToChangeTicket.email'), $ctx);
        $this->assertStringContainsString('Старое Имя', $html);
        $this->assertStringContainsString('Новое Имя', $html);

        // @foreach($questionnaireLinks) → {{# questionnaireLinks }} (оба гостя + ссылки)
        $html = $renderer->render($this->tpl('TypeTicketMailOrderToPaidChild.email'), $ctx);
        $this->assertStringContainsString('Гость Один', $html);
        $this->assertStringContainsString('Гость Два', $html);
        $this->assertStringContainsString('https://q.example/1', $html);

        // friendly-вариант детской анкеты — те же секции
        $html = $renderer->render($this->tpl('TypeTicketMailOrderToPaidChildFriendly.email'), $ctx);
        $this->assertStringContainsString('Гость Один', $html);

        // @if($promocode) → {{# promocode }}
        $html = $renderer->render($this->tpl('orderToPaid.email'), $ctx);
        $this->assertStringContainsString('TRANSFER2026', $html);
        $this->assertStringContainsString('spacetransfer.ru', $html);

        // @if(!empty($locationName)) → {{# locationName }}
        $html = $renderer->render($this->tpl('orderListApproved.email'), $ctx);
        $this->assertStringContainsString('Главная поляна', $html);
    }

    /** Секции схлопываются без данных (инверсия логики blade @if). */
    public function test_sections_collapse_without_data(): void
    {
        $renderer = app(TemplateRenderer::class);
        $empty = ['year' => '2026', 'festivalName' => 'Solar Systo 2026', 'changes' => [], 'questionnaireLinks' => []];

        // orderToPaid без промокода → блок про трансфер отсутствует
        $html = $renderer->render($this->tpl('orderToPaid.email'), $empty);
        $this->assertStringNotContainsString('spacetransfer.ru', $html);
        $this->assertStringNotContainsString('TRANSFER2026', $html);

        // orderListApproved без локации → строки «Локация:» нет
        $html = $renderer->render($this->tpl('orderListApproved.email'), $empty);
        $this->assertStringNotContainsString('Локация:', $html);

        // orderToChangeTicket без изменений → ни одного билета в цикле
        $html = $renderer->render($this->tpl('orderToChangeTicket.email'), $empty);
        $this->assertStringNotContainsString('аннулирован', $html);
    }

    /** {{ var }} экранирует HTML, {{{ url }}} (QR) идёт raw, {{ year }} подставляется. */
    public function test_escaping_and_raw_url_in_pdf(): void
    {
        $renderer = app(TemplateRenderer::class);
        $ctx = [
            'year' => '2026',
            'name' => '<b>Аня</b>',
            'email' => 'a@b.c',
            'kilter' => 7,
            'url' => 'data:image/png;base64,AAA==',
        ];
        $html = $renderer->render($this->tpl('TypeTicketPdf1.pdf'), $ctx);

        // QR data-URI — raw ({{{ url }}}), не экранирован.
        $this->assertStringContainsString('src="data:image/png;base64,AAA=="', $html);
        // Имя — экранировано ({{ name }}): теги превращены в сущности.
        $this->assertStringContainsString('&lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>Аня</b>', $html);
        // year подставлен.
        $this->assertStringContainsString('2026', $html);
        $this->assertStringContainsString('ID: E-7', $html);
    }

    private function tpl(string $name): string
    {
        return file_get_contents(resource_path("views/mustache/{$name}.mustache"));
    }
}
