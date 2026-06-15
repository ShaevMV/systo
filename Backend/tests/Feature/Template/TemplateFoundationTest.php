<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateModel;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Service\TemplateRenderer;

/**
 * Фаза 1 системы шаблонов: движок рендера (Mustache) + безопасность + репозиторий (findActive/CRUD).
 */
class TemplateFoundationTest extends TestCase
{
    private function renderer(): TemplateRenderer
    {
        return new TemplateRenderer();
    }

    private function repo(): TemplateRepositoryInterface
    {
        return app(TemplateRepositoryInterface::class);
    }

    private function makeTemplate(array $overrides = []): TemplateModel
    {
        return TemplateModel::create(array_merge([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            'slug' => 'orderToPaid',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Оплата заказа',
            'body' => 'Привет, {{ order.email }}',
            'active' => true,
            'is_system' => false,
        ], $overrides));
    }

    // ─── Рендер ────────────────────────────────────────────────────────────────

    public function test_renders_variable(): void
    {
        $out = $this->renderer()->render('Привет, {{ name }}!', ['name' => 'Мир']);
        $this->assertSame('Привет, Мир!', $out);
    }

    public function test_renders_section_and_inverted(): void
    {
        $tpl = '{{#paid}}оплачено{{/paid}}{{^paid}}нет{{/paid}}';
        $this->assertSame('оплачено', $this->renderer()->render($tpl, ['paid' => true]));
        $this->assertSame('нет', $this->renderer()->render($tpl, ['paid' => false]));
    }

    public function test_renders_loop_over_guests(): void
    {
        $out = $this->renderer()->render('{{#guests}}[{{name}}]{{/guests}}', [
            'guests' => [['name' => 'Аня'], ['name' => 'Боря']],
        ]);
        $this->assertSame('[Аня][Боря]', $out);
    }

    /** КЛЮЧЕВОЙ тест безопасности: PHP в шаблоне НЕ исполняется, выводится как экранированный текст. */
    public function test_php_in_template_is_not_executed(): void
    {
        $malicious = '{{ code }}';
        $out = $this->renderer()->render($malicious, ['code' => '<?php system("rm -rf /"); ?>']);

        $this->assertStringNotContainsString('<?php', $out);
        $this->assertStringContainsString('&lt;?php', $out);
    }

    /** Плейсхолдер прямо в теле шаблона (а не в данных) тоже не исполняется — Mustache его не трогает. */
    public function test_literal_php_in_body_is_inert(): void
    {
        $out = $this->renderer()->render('<?php echo 1; ?>{{ x }}', ['x' => 'ok']);
        // <?php остаётся как есть (Mustache не компилирует PHP), подстановка сработала
        $this->assertStringContainsString('<?php echo 1; ?>', $out);
        $this->assertStringContainsString('ok', $out);
    }

    public function test_raw_triple_mustache_not_escaped(): void
    {
        // QR-код приходит как data-URI — нужен raw-вывод без экранирования.
        $out = $this->renderer()->render('<img src="{{{ url }}}">', ['url' => 'data:image/png;base64,AAA==']);
        $this->assertStringContainsString('src="data:image/png;base64,AAA=="', $out);
    }

    // ─── Репозиторий ─────────────────────────────────────────────────────────────

    public function test_find_active_returns_active_template(): void
    {
        $this->makeTemplate(['slug' => 'pdf', 'kind' => TemplateKind::PDF, 'active' => true]);

        $dto = $this->repo()->findActive('pdf', TemplateKind::PDF);

        $this->assertNotNull($dto);
        $this->assertSame('pdf', $dto->getSlug());
        $this->assertSame(TemplateKind::PDF, $dto->getKind());
    }

    public function test_find_active_returns_null_for_inactive(): void
    {
        $this->makeTemplate(['slug' => 'pdf', 'kind' => TemplateKind::PDF, 'active' => false]);

        $this->assertNull($this->repo()->findActive('pdf', TemplateKind::PDF));
    }

    public function test_find_active_returns_null_when_missing(): void
    {
        $this->assertNull($this->repo()->findActive('nope', TemplateKind::EMAIL));
    }

    public function test_create_and_get_item_roundtrip(): void
    {
        $id = Uuid::random();
        $dto = TemplateDto::fromState([
            'id' => $id->value(),
            'slug' => 'orderToCancel',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'Отмена',
            'body' => 'Заказ отменён, {{ order.email }}',
            'active' => true,
        ]);

        $this->assertTrue($this->repo()->create($dto));

        $loaded = $this->repo()->getItem($id);
        $this->assertSame('orderToCancel', $loaded->getSlug());
        $this->assertSame('Заказ отменён, {{ order.email }}', $loaded->getBody());
        $this->assertTrue($loaded->getActive());
    }

    public function test_activate_toggles_flag(): void
    {
        $model = $this->makeTemplate(['active' => true]);
        $id = new Uuid($model->id);

        $this->repo()->activate($id, false);
        $this->assertFalse($this->repo()->getItem($id)->getActive());

        $this->repo()->activate($id, true);
        $this->assertTrue($this->repo()->getItem($id)->getActive());
    }

    public function test_find_active_isolates_by_kind(): void
    {
        // Один slug, две записи разных kind — резолв не путает письмо и PDF.
        $this->makeTemplate(['slug' => 'shared', 'kind' => TemplateKind::EMAIL, 'body' => 'EMAIL']);
        $this->makeTemplate(['slug' => 'shared', 'kind' => TemplateKind::PDF, 'body' => 'PDF']);

        $this->assertSame('EMAIL', $this->repo()->findActive('shared', TemplateKind::EMAIL)->getBody());
        $this->assertSame('PDF', $this->repo()->findActive('shared', TemplateKind::PDF)->getBody());
    }
}
