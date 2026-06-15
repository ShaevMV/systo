<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateBindingModel;
use App\Models\Template\TemplateModel;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\TestCase;
use Tickets\Template\Domain\TemplateKind;
use Tickets\TemplateBinding\Application\TemplateBindingApplication;

/**
 * Часть B: e2e резолва через БД — репозиторий джойнит привязку к templates (берёт slug),
 * резолвер выбирает его. Проверяет реальную связку join → resolveSlug.
 */
class TemplateBindingResolveTest extends TestCase
{
    private function makeTemplate(string $slug, string $kind): string
    {
        return TemplateModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'slug' => $slug,
            'kind' => $kind,
            'engine' => 'html',
            'title' => $slug,
            'body' => 'B',
            'active' => true,
            'is_system' => false,
        ])->id;
    }

    public function test_binding_resolves_to_bound_template_slug(): void
    {
        $tplId = $this->makeTemplate('friendlySpecial', TemplateKind::EMAIL);
        TemplateBindingModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'order_type' => 'friendly',
            'email_template_id' => $tplId,
            'is_default' => false,
            'active' => true,
        ]);

        $application = app(TemplateBindingApplication::class);

        // friendly → берётся slug привязанного шаблона.
        $this->assertSame('friendlySpecial', $application->resolveSlug('email', 'any-fest', 'friendly', 'any-tt'));
        // regular → привязки нет, дефолта нет → null (старое поведение).
        $this->assertNull($application->resolveSlug('email', 'any-fest', 'regular', 'any-tt'));
        // pdf-резолв этой email-привязки → null (slug только для email).
        $this->assertNull($application->resolveSlug('pdf', 'any-fest', 'friendly', 'any-tt'));
    }

    public function test_default_binding_is_fallback(): void
    {
        $tplId = $this->makeTemplate('defaultMail', TemplateKind::EMAIL);
        TemplateBindingModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'is_default' => true,
            'email_template_id' => $tplId,
            'active' => true,
        ]);

        $application = app(TemplateBindingApplication::class);
        // Любой заказ без точной привязки → дефолт.
        $this->assertSame('defaultMail', $application->resolveSlug('email', 'fest', 'regular', 'tt'));
    }
}
