<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use App\Models\Template\TemplateBindingModel;
use App\Models\Template\TemplateModel;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\TestCase;
use Tickets\EmailDelivery\Domain\EmailEvent;
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

        // friendly → берётся slug привязанного шаблона (событие не задано → wildcard).
        $this->assertSame('friendlySpecial', $application->resolveSlug('email', null, 'any-fest', 'friendly', 'any-tt'));
        // regular → привязки нет, дефолта нет → null (старое поведение).
        $this->assertNull($application->resolveSlug('email', null, 'any-fest', 'regular', 'any-tt'));
        // pdf-резолв этой email-привязки → null (slug только для email).
        $this->assertNull($application->resolveSlug('pdf', null, 'any-fest', 'friendly', 'any-tt'));
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
        $this->assertSame('defaultMail', $application->resolveSlug('email', null, 'fest', 'regular', 'tt'));
    }

    public function test_event_specific_binding_resolves_through_db(): void
    {
        $tplId = $this->makeTemplate('cancelSpecial', TemplateKind::EMAIL);
        TemplateBindingModel::create([
            'id' => RamseyUuid::uuid4()->toString(),
            'event' => EmailEvent::ORDER_CANCEL,
            'email_template_id' => $tplId,
            'is_default' => false,
            'active' => true,
        ]);

        $application = app(TemplateBindingApplication::class);

        // Письмо отмены → берётся привязка события отмены.
        $this->assertSame('cancelSpecial', $application->resolveSlug('email', EmailEvent::ORDER_CANCEL, 'fest', 'regular', 'tt'));
        // Письмо оплаты → эта привязка не подходит, дефолта нет → null.
        $this->assertNull($application->resolveSlug('email', EmailEvent::ORDER_PAID, 'fest', 'regular', 'tt'));
    }
}
