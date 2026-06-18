<?php

declare(strict_types=1);

namespace Tests\Feature\Template;

use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\TemplateBinding\Domain\TemplateBindingResolver;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;

/**
 * Часть B + ось «событие»: UNIT-проверка чистой логики выбора привязки шаблона — без БД.
 * Главный тест фичи: специфичность (event > ticket > order > festival), wildcard, дефолт-fallback,
 * разделение email/pdf, активность, и матчинг по событию письма.
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
            'event' => null,
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

        $this->assertSame('exact', $this->resolver->resolve($bindings, 'email', null, self::FEST, 'friendly', self::TT));
    }

    public function test_partial_wildcard_order_type_only(): void
    {
        $bindings = [$this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail'])];

        // Любой фестиваль/билет, но friendly → подходит.
        $this->assertSame('friendlyMail', $this->resolver->resolve($bindings, 'email', null, 'any-fest', 'friendly', 'any-tt'));
        // regular → не подходит, дефолта нет → null.
        $this->assertNull($this->resolver->resolve($bindings, 'email', null, 'any-fest', 'regular', 'any-tt'));
    }

    public function test_specificity_ticket_beats_order(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'regular', 'email_slug' => 'byOrder']),     // спец. 2
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'byTicket']), // спец. 4
        ];

        $this->assertSame('byTicket', $this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
    }

    public function test_falls_back_to_default(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail']),
            $this->binding(['is_default' => true, 'email_slug' => 'defaultMail']),
        ];

        // regular не совпал с friendly → дефолт.
        $this->assertSame('defaultMail', $this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
    }

    public function test_no_match_no_default_returns_null(): void
    {
        $bindings = [$this->binding(['order_type' => 'friendly', 'email_slug' => 'friendlyMail'])];

        $this->assertNull($this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
    }

    public function test_email_and_pdf_separated(): void
    {
        $bindings = [
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'mailA']),            // pdf_slug null
            $this->binding(['is_default' => true, 'pdf_slug' => 'defaultPdf', 'email_slug' => 'defaultMail']),
        ];

        // email → точная привязка.
        $this->assertSame('mailA', $this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
        // pdf → у точной привязки нет pdf_slug → дефолт.
        $this->assertSame('defaultPdf', $this->resolver->resolve($bindings, 'pdf', null, self::FEST, 'regular', self::TT));
    }

    public function test_inactive_bindings_ignored(): void
    {
        $bindings = [
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'inactive', 'active' => false]),
            $this->binding(['is_default' => true, 'email_slug' => 'defaultMail']),
        ];

        // Неактивная точная привязка игнорируется → дефолт.
        $this->assertSame('defaultMail', $this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
    }

    // --- Ось «событие» -----------------------------------------------------

    public function test_event_specific_binding_selected_for_matching_event(): void
    {
        $bindings = [
            $this->binding(['event' => EmailEvent::ORDER_CANCEL, 'email_slug' => 'cancelMail']),
        ];

        // Письмо отмены → берётся привязка события отмены.
        $this->assertSame('cancelMail', $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_CANCEL, self::FEST, 'regular', self::TT));
    }

    public function test_event_binding_ignored_for_other_event(): void
    {
        $bindings = [
            $this->binding(['event' => EmailEvent::ORDER_CANCEL, 'email_slug' => 'cancelMail']),
        ];

        // Запрос события оплаты не должен матчить привязку события отмены (дефолта нет → null).
        $this->assertNull($this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT));
    }

    public function test_null_event_binding_is_wildcard_for_any_event(): void
    {
        $bindings = [
            $this->binding(['order_type' => 'regular', 'email_slug' => 'anyEventMail']), // event = null = любое
        ];

        // event = null в привязке подходит под любое событие запроса.
        $this->assertSame('anyEventMail', $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_CANCEL, self::FEST, 'regular', self::TT));
    }

    public function test_event_query_null_matches_only_null_event_binding(): void
    {
        $bindings = [
            $this->binding(['event' => EmailEvent::ORDER_PAID, 'email_slug' => 'paidMail']),
        ];

        // Резолв без события (PDF/выдача, event = null) не должен матчить привязку с конкретным событием.
        $this->assertNull($this->resolver->resolve($bindings, 'email', null, self::FEST, 'regular', self::TT));
    }

    public function test_event_specificity_beats_ticket_type(): void
    {
        $bindings = [
            $this->binding(['ticket_type_id' => self::TT, 'email_slug' => 'byTicket']),               // спец. 4
            $this->binding(['event' => EmailEvent::ORDER_PAID, 'email_slug' => 'byEvent']),           // спец. 8
        ];

        // Обе подходят под (event=order_paid, ticket=TT); событие (вес 8) важнее типа билета (вес 4).
        $this->assertSame('byEvent', $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT));
    }

    // --- Ось «тип оплаты» (= продавец/магазин, AF-9) -----------------------

    public function test_payment_type_binding_selected_for_matching_payment(): void
    {
        $bindings = [
            $this->binding(['types_of_payment_id' => 'pay-1', 'email_slug' => 'sellerMail']),
        ];

        // Письмо по заказу с этим типом оплаты (продавцом) → берётся привязка продавца.
        $this->assertSame(
            'sellerMail',
            $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT, 'pay-1'),
        );
    }

    public function test_payment_type_binding_ignored_for_other_payment(): void
    {
        $bindings = [
            $this->binding(['types_of_payment_id' => 'pay-1', 'email_slug' => 'sellerMail']),
        ];

        // Другой тип оплаты → привязка продавца не матчит (дефолта нет → null).
        $this->assertNull(
            $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT, 'pay-2'),
        );
    }

    public function test_payment_type_specificity_beats_event(): void
    {
        $bindings = [
            $this->binding(['event' => EmailEvent::ORDER_PAID, 'email_slug' => 'byEvent']),     // спец. 8
            $this->binding(['types_of_payment_id' => 'pay-1', 'email_slug' => 'bySeller']),      // спец. 16
        ];

        // Обе подходят; тип оплаты (продавец, вес 16) — сильнейший override, важнее события (8).
        $this->assertSame(
            'bySeller',
            $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT, 'pay-1'),
        );
    }

    public function test_payment_type_and_event_most_specific_wins(): void
    {
        $bindings = [
            $this->binding(['types_of_payment_id' => 'pay-1', 'email_slug' => 'sellerAny']),                                   // спец. 16
            $this->binding(['types_of_payment_id' => 'pay-1', 'event' => EmailEvent::ORDER_PAID, 'email_slug' => 'sellerPaid']), // спец. 24
        ];

        // Привязка (продавец + событие) специфичнее, чем только продавец.
        $this->assertSame(
            'sellerPaid',
            $this->resolver->resolve($bindings, 'email', EmailEvent::ORDER_PAID, self::FEST, 'regular', self::TT, 'pay-1'),
        );
    }
}
