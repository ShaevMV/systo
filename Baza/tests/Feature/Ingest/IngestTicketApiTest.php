<?php

declare(strict_types=1);

namespace Tests\Feature\Ingest;

use App\Models\AutoModel;
use App\Models\ElTicketsModel;
use App\Models\LiveTicketModel;
use App\Models\SpisokTicketModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * S2S-приём билетов от org через ingest-API (Ф3): POST /api/baza/ingest/ticket.
 *
 * Канал закрыт middleware baza.ingest (заголовок X-Baza-Token). Идемпотентно по
 * естественному ключу цели: el → uuid, spisok → ticket_uuid, live → kilter, auto → (order_id,auto).
 * БД baza_test (phpunit.xml).
 */
class IngestTicketApiTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-baza-ingest-token';

    private const URL = '/api/baza/ingest/ticket';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.baza_ingest.tokens' => [self::TOKEN]]);
    }

    /** @return array<string,string> */
    private function authHeader(): array
    {
        return ['X-Baza-Token' => self::TOKEN];
    }

    public function test_rejects_without_token(): void
    {
        $this->postJson(self::URL, ['target' => 'el_tickets', 'ticket' => ['uuid' => 'x']])
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_rejects_with_bad_token(): void
    {
        $this->postJson(self::URL, ['target' => 'el_tickets', 'ticket' => ['uuid' => 'x']], ['X-Baza-Token' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_el_ticket_insert_then_idempotent_update(): void
    {
        $uuid = '11111111-1111-4111-8111-111111111111';
        $ticket = [
            'kilter' => 101,
            'uuid' => $uuid,
            'city' => 'Москва',
            'name' => 'Иван Иванов',
            'email' => 'ivan@example.com',
            'phone' => '+70000000000',
            'date_order' => Carbon::parse('2026-06-20T10:00:00+03:00')->toIso8601String(),
            'status' => 'paid',
            'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
        ];

        $this->postJson(self::URL, ['target' => 'el_tickets', 'ticket' => $ticket], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => true, 'target' => 'el_tickets']);

        self::assertSame(1, ElTicketsModel::where('uuid', $uuid)->count());
        self::assertSame('Иван Иванов', ElTicketsModel::where('uuid', $uuid)->value('name'));

        // Повтор с новым именем — без дубля, имя обновилось (идемпотентность по uuid).
        $ticket['name'] = 'Иван Петров';
        $this->postJson(self::URL, ['target' => 'el_tickets', 'ticket' => $ticket], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        self::assertSame(1, ElTicketsModel::where('uuid', $uuid)->count());
        self::assertSame('Иван Петров', ElTicketsModel::where('uuid', $uuid)->value('name'));
    }

    public function test_spisok_ticket_insert_by_ticket_uuid(): void
    {
        $ticketUuid = '22222222-2222-4222-8222-222222222222';
        $ticket = [
            'kilter' => 202,
            'project' => 'Сцена А',
            'curator' => 'curator@example.com',
            'email' => 'guest@example.com',
            'name' => 'Гость Списка',
            'date_order' => Carbon::now()->toIso8601String(),
            'status' => 'paid',
            'ticket_uuid' => $ticketUuid,
            'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b2',
        ];

        $this->postJson(self::URL, ['target' => 'spisok_tickets', 'ticket' => $ticket], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        self::assertSame(1, SpisokTicketModel::where('ticket_uuid', $ticketUuid)->count());
    }

    public function test_live_ticket_links_existing_row(): void
    {
        $now = Carbon::now()->format('Y-m-d H:i:s');
        DB::table(LiveTicketModel::TABLE)->insert([
            'kilter' => 777,
            'status' => 'paid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $elId = '33333333-3333-4333-8333-333333333333';
        $this->postJson(self::URL, [
            'target' => 'live_tickets',
            'ticket' => ['kilter' => 777, 'el_ticket_id' => $elId],
        ], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        self::assertSame($elId, DB::table(LiveTicketModel::TABLE)->where('kilter', 777)->value('el_ticket_id'));
    }

    public function test_live_ticket_absent_row_returns_success_false(): void
    {
        // Нет строки live-билета с таким номером → не применено (org откатится на прямую запись).
        $this->postJson(self::URL, [
            'target' => 'live_tickets',
            'ticket' => ['kilter' => 999999, 'el_ticket_id' => '44444444-4444-4444-8444-444444444444'],
        ], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => false]);
    }

    public function test_auto_insert_by_order_and_number(): void
    {
        $orderId = '55555555-5555-4555-8555-555555555555';
        $this->postJson(self::URL, [
            'target' => 'auto',
            'ticket' => [
                'order_id' => $orderId,
                'auto' => 'А123АА777',
                'curator' => 'cur@example.com',
                'project' => 'Парковка',
                'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b4',
            ],
        ], $this->authHeader())
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        self::assertSame(1, AutoModel::where('order_id', $orderId)->where('auto', 'А123АА777')->count());
    }

    public function test_unknown_target_returns_422(): void
    {
        $this->postJson(self::URL, ['target' => 'nope', 'ticket' => []], $this->authHeader())
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_el_without_uuid_returns_422(): void
    {
        $this->postJson(self::URL, ['target' => 'el_tickets', 'ticket' => ['name' => 'No UUID']], $this->authHeader())
            ->assertStatus(422);
    }
}
