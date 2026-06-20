<?php

declare(strict_types=1);

namespace Tests\Feature\BazaWebhook;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Ф4: приём вебхука «билет прошёл» от Baza (POST /api/v1/baza/ticketEntered).
 *
 * S2S-канал (X-Baza-Token, middleware baza.webhook). Пишет факт входа в domain_history
 * (actor_type=baza), идемпотентно по event_id.
 */
class BazaWebhookApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-baza-webhook-token';

    private const URL = '/api/v1/baza/ticketEntered';

    private const TICKET_UUID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.baza_webhook.tokens' => [self::TOKEN]]);
    }

    /** @return array<string,string> */
    private function auth(): array
    {
        return ['X-Baza-Token' => self::TOKEN];
    }

    /** @return array<string,mixed> */
    private function payload(string $eventId = 'evt-1'): array
    {
        return [
            'event_id' => $eventId,
            'ticket_uuid' => self::TICKET_UUID,
            'target' => 'el_tickets',
            'kilter' => 770077,
            'change_id' => 3,
            'entered_at' => '2026-06-20 12:00:00',
        ];
    }

    public function test_rejects_without_token(): void
    {
        $this->postJson(self::URL, $this->payload())->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_rejects_with_bad_token(): void
    {
        $this->postJson(self::URL, $this->payload(), ['X-Baza-Token' => 'wrong'])->assertStatus(401);
    }

    public function test_records_entry_in_history(): void
    {
        $this->postJson(self::URL, $this->payload(), $this->auth())
            ->assertStatus(200)
            ->assertJson(['success' => true, 'recorded' => true]);

        $events = app(HistoryRepositoryInterface::class)->getByAggregateId(self::TICKET_UUID);
        self::assertCount(1, $events);
        self::assertSame('ticket_entered', $events[0]->eventName);
        self::assertSame('baza', $events[0]->actorType);
        self::assertSame('el_tickets', $events[0]->payload['target']);
        self::assertSame(770077, $events[0]->payload['kilter']);
    }

    public function test_idempotent_by_event_id(): void
    {
        $this->postJson(self::URL, $this->payload('evt-dup'), $this->auth())->assertStatus(200);
        // Повтор того же event_id (ретрай дренажа) — не создаёт дубль.
        $this->postJson(self::URL, $this->payload('evt-dup'), $this->auth())
            ->assertStatus(200)
            ->assertJson(['success' => true, 'recorded' => false]);

        self::assertCount(1, app(HistoryRepositoryInterface::class)->getByAggregateId(self::TICKET_UUID));
    }

    public function test_missing_ticket_uuid_returns_422(): void
    {
        $payload = $this->payload();
        unset($payload['ticket_uuid']);

        $this->postJson(self::URL, $payload, $this->auth())->assertStatus(422)->assertJson(['success' => false]);
    }
}
