<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\TemplateBinding\Domain\TemplateBindingResolver;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;

/**
 * Часть B: UNIT-проверка чистой логики выбора привязки шаблона — без БД.
 * Главный тест фичи: специфичность, wildcard, дефолт-fallback, разделение email/pdf, активность.
 */
class TemplateBindingResolverTest extends TestCase
{
    private const FEST = 'fest-1';
    private const TT = 'tt-1';

    private TemplateBindingResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new TemplateBindingResolver();
    }

    private function binding(array $overrides): TemplateBindingDto
    {
        return TemplateBindingDto::fromState(array_merge([
            'id' => Uuid::random()->value(),
            'festival_id' => null,
            'order_type' => null,
            'ticket_type_id' => null,
            'email_template_id' => null,
            'pdf_template_id' => null,
            'is_default' => false,
            'active' => true,
            'email_slug' => null,
            'pdf_slug' => null,
        ], $overrides));
    }

    public function test_exact_match_beats_wildcard(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'friendly', 'email_slug' => 'wildcardFriendly']),
            $this->binding(['festival_id' => self::FEST, 'order_type' => 'friendly', 'ticket_type_id' => self::TT, 'email_slug' => 'exact']),
        ];

        $this->assertSame('exact', $this->resolver->resolve($bindings, 'email', self::FEST, 'friendly', self::TT));
    }

    public function test_partial_wildcard_order_type_only(): void
    {
        $bindings = [$this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail'])];

        // Любой фестиваль/билет, но friendly → подходит.
        $this->assertSame('friendlyMail', $this->resolver->resolve($bindings, 'email', 'any-fest', 'friendly', 'any-tt'));
        // regular → не подходит, дефолта нет → null.
        $this->assertNull($this->resolver->resolve($bindings, 'email', 'any-fest', 'regular', 'any-tt'));
    }

    public function test_specificity_ticket_beats_order(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'regular', 'email_slug' => 'byOrder']),     // спец. 2
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'byTicket']), // спец. 4
        ];

        $this->assertSame('byTicket', $this->resolver->resolve($bindings, 'email', self::FEST, 'regular', self::TT));
    }

    public function test_falls_back_to_default(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail']),
            $this->binding(['is_default' => true, 'email_slug' => 'defaultMail']),
        ];

        // regular не совпал с friendly → дефолт.
        $this->assertSame('defaultMail', $this->resolver->resolve($bindings, 'email', self::FEST, 'regular', self::TT));
    }

    public function test_no_match_no_default_returns_null(): void
    {
        $bindings = [$this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail'])];

        $this->assertNull($this->resolver->resolve($bindings, 'email', self::FEST, 'regular', self::TT));
    }

    public function test_email_and_pdf_separated(): void
    {
        $bindings = [
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'mailA']),            // pdf_slug null
            $this->binding(['is_default' => true, 'pdf_slug' => 'defaultPdf', 'email_slug' => 'defaultMail']),
        ];

        // email → точная привязка.
        $this->assertSame('mailA', $this->resolver->resolve($bindings, 'email', self::FEST, 'regular', self::TT));
        // pdf → у точной привязки нет pdf_slug → дефолт.
        $this->assertSame('defaultPdf', $this->resolver->resolve($bindings, 'pdf', self::FEST, 'regular', self::TT));
    }

    public function test_inactive_bindings_ignored(): void
    {
        $bindings = [
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'inactive', 'active' => false]),
            $this->binding(['is_default' => true, 'email_slug' => 'defaultMail']),
        ];

        // Неактивная точная привязка игнорируется → дефолт.
        $this->assertSame('defaultMail', $this->resolver->resolve($bindings, 'email', self::FEST, 'regular', self::TT));
    }
}
