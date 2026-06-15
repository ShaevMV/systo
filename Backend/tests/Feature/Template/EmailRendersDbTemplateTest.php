<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Mail\Concerns\RendersDbTemplate;
use App\Models\Template\TemplateModel;
use Illuminate\Mail\Mailable;
use InvalidArgumentException;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;

/**
 * Рендер тела письма из активного DB-шаблона (трейт RendersDbTemplate) с fallback на blade.
 */
class EmailRendersDbTemplateTest extends TestCase
{
    /** Тестовый Mailable, использующий трейт. */
    private function mailable(string $slug, array $vars): Mailable
    {
        return new class($slug, $vars) extends Mailable {
            use RendersDbTemplate;

            public function __construct(private string $slug, private array $vars)
            {
            }

            public function build(): static
            {
                $this->subject('test');

                return $this->renderDbOrView($this->slug, $this->vars);
            }
        };
    }

    private function makeEmailTemplate(array $overrides = []): void
    {
        TemplateModel::create(array_merge([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            'slug' => 'phase2_demo',
            'kind' => TemplateKind::EMAIL,
            'engine' => 'html',
            'title' => 'demo',
            'body' => 'Фестиваль: {{ festivalName }}{{#promocode}} промо {{ promocode }}{{/promocode}}',
            'active' => true,
            'is_system' => false,
        ], $overrides));
    }

    public function test_renders_email_body_from_active_db_template(): void
    {
        $this->makeEmailTemplate();

        $html = $this->mailable('phase2_demo', ['festivalName' => 'СОЛАР', 'promocode' => 'SUN10'])->render();

        $this->assertStringContainsString('Фестиваль: СОЛАР', $html);
        $this->assertStringContainsString('промо SUN10', $html);
    }

    public function test_inverted_section_when_promo_absent(): void
    {
        $this->makeEmailTemplate();

        $html = $this->mailable('phase2_demo', ['festivalName' => 'СОЛАР'])->render();

        $this->assertStringContainsString('Фестиваль: СОЛАР', $html);
        $this->assertStringNotContainsString('промо', $html);
    }

    public function test_falls_back_to_blade_when_no_active_template(): void
    {
        // Нет записи templates для slug → trait зовёт $this->view('email.phase2_demo'),
        // такого blade нет → исключение view-not-found = доказательство, что выбран fallback на blade.
        $this->expectException(InvalidArgumentException::class);

        $this->mailable('phase2_demo', ['festivalName' => 'X'])->render();
    }

    public function test_inactive_template_is_not_used_falls_back(): void
    {
        $this->makeEmailTemplate(['active' => false]);

        // Неактивный DB-шаблон игнорируется → fallback на несуществующий blade → исключение.
        $this->expectException(InvalidArgumentException::class);

        $this->mailable('phase2_demo', ['festivalName' => 'X'])->render();
    }
}
