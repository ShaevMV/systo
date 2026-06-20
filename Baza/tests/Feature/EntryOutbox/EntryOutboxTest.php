<?php

declare(strict_types=1);

namespace Tests\Feature\EntryOutbox;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Baza\EntryOutbox\Applications\EntryOutboxApplication;
use Baza\EntryOutbox\Repositories\EntryOutboxRepositoryInterface;
use Baza\Shared\Services\DefineService;
use Carbon\Carbon;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;

/**
 * Ф4: outbox вебхука «билет прошёл» Baza→org.
 *
 * Впуск пишет запись в baza_entry_outbox (best-effort), дренаж шлёт её на org при наличии сети.
 * Канал выключен (нет ORG_WEBHOOK_URL/TOKEN) → буфер копится локально, вход работает.
 * БД baza_test (phpunit.xml).
 */
class EntryOutboxTest extends TestCase
{
    use RefreshDatabase;

    private const KILTER = 770077;

    private const TICKET_UUID = '11111111-1111-1111-1111-111111111111';

    private const FESTIVAL_ID = ChangesTestDataSeeder::FESTIVAL_ID;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);
    }

    private function createElTicket(): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => self::KILTER,
            'uuid' => self::TICKET_UUID,
            'city' => 'Москва',
            'name' => 'Тест Гость',
            'email' => 'guest@example.com',
            'phone' => '+70000000000',
            'date_order' => now(),
            'status' => 'paid',
            'type_ticket' => 'Электронный',
            'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0,
            'change_id' => null,
            'date_change' => null,
            'festival_id' => self::FESTIVAL_ID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_enter_writes_outbox_row_with_resolved_uuid(): void
    {
        $this->createElTicket();

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::ELECTRON_TICKET, 'id' => self::KILTER])
            ->assertOk();

        $row = DB::table('baza_entry_outbox')->where('ticket_uuid', self::TICKET_UUID)->first();
        self::assertNotNull($row, 'Впуск должен создать запись в outbox');
        self::assertSame('el_tickets', $row->target);
        self::assertSame('pending', $row->status);
        self::assertSame(self::KILTER, (int) $row->kilter);
    }

    public function test_resolve_ticket_uuid_per_target(): void
    {
        $repo = app(EntryOutboxRepositoryInterface::class);
        $this->createElTicket();
        DB::table('spisok_tickets')->insert([
            'kilter' => 5001, 'project' => 'P', 'curator' => 'c@e.ru', 'email' => 'g@e.ru',
            'name' => 'N', 'date_order' => now(), 'status' => 'paid', 'comment' => '',
            'ticket_uuid' => 'aaaa1111-1111-1111-1111-111111111111',
            'festival_id' => self::FESTIVAL_ID, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('live_tickets')->insert([
            'kilter' => 6001, 'status' => 'paid',
            'el_ticket_id' => 'bbbb2222-2222-2222-2222-222222222222',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $autoId = DB::table('auto')->insertGetId([
            'order_id' => 'cccc3333-3333-3333-3333-333333333333', 'curator' => 'c', 'project' => 'p',
            'auto' => 'А123АА', 'festival_id' => self::FESTIVAL_ID, 'created_at' => now(), 'updated_at' => now(),
        ]);

        self::assertSame(self::TICKET_UUID, $repo->resolveTicketUuid(DefineService::ELECTRON_TICKET, self::KILTER));
        self::assertSame('aaaa1111-1111-1111-1111-111111111111', $repo->resolveTicketUuid(DefineService::SPISOK_TICKET, 5001));
        self::assertSame('bbbb2222-2222-2222-2222-222222222222', $repo->resolveTicketUuid(DefineService::LIVE_TICKET, 6001));
        self::assertSame('cccc3333-3333-3333-3333-333333333333', $repo->resolveTicketUuid(DefineService::AUTO_TICKET, $autoId));
        self::assertNull($repo->resolveTicketUuid(DefineService::DRUG_TICKET, 1));
    }

    public function test_record_is_idempotent(): void
    {
        $this->createElTicket();
        $app = app(EntryOutboxApplication::class);

        $app->record(DefineService::ELECTRON_TICKET, self::KILTER, 1);
        $app->record(DefineService::ELECTRON_TICKET, self::KILTER, 1);

        self::assertSame(1, DB::table('baza_entry_outbox')->where('ticket_uuid', self::TICKET_UUID)->count());
    }

    public function test_record_skips_when_no_org_identifier(): void
    {
        // live без el_ticket_id → нет org-идентификатора → запись не создаётся.
        DB::table('live_tickets')->insert([
            'kilter' => 6002, 'status' => 'paid', 'el_ticket_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        app(EntryOutboxApplication::class)->record(DefineService::LIVE_TICKET, 6002, 1);

        self::assertSame(0, DB::table('baza_entry_outbox')->count());
    }

    public function test_drain_disabled_keeps_rows_pending(): void
    {
        config(['services.org_webhook.url' => null, 'services.org_webhook.token' => null]);
        Http::fake();
        $this->seedPendingRow();

        $sent = app(EntryOutboxApplication::class)->drain();

        self::assertSame(0, $sent);
        self::assertSame('pending', DB::table('baza_entry_outbox')->value('status'));
        Http::assertNothingSent();
    }

    public function test_drain_sends_and_marks_sent_on_success(): void
    {
        config(['services.org_webhook.url' => 'https://org.test', 'services.org_webhook.token' => 'tok']);
        Http::fake(['https://org.test/*' => Http::response(['success' => true], 200)]);
        $id = $this->seedPendingRow();

        $sent = app(EntryOutboxApplication::class)->drain();

        self::assertSame(1, $sent);
        self::assertSame('sent', DB::table('baza_entry_outbox')->where('id', $id)->value('status'));
        Http::assertSent(fn ($req) => $req->url() === 'https://org.test/api/v1/baza/ticketEntered'
            && $req['event_id'] === $id
            && $req->hasHeader('X-Baza-Token', 'tok'));
    }

    public function test_drain_marks_failed_on_http_error(): void
    {
        config(['services.org_webhook.url' => 'https://org.test', 'services.org_webhook.token' => 'tok']);
        Http::fake(['https://org.test/*' => Http::response('boom', 500)]);
        $id = $this->seedPendingRow();

        $sent = app(EntryOutboxApplication::class)->drain();

        self::assertSame(0, $sent);
        $row = DB::table('baza_entry_outbox')->where('id', $id)->first();
        self::assertSame('failed', $row->status);
        self::assertSame(1, (int) $row->attempts);
    }

    private function seedPendingRow(): string
    {
        $id = (string) Uuid::random()->value();
        DB::table('baza_entry_outbox')->insert([
            'id' => $id, 'target' => 'el_tickets', 'ticket_uuid' => self::TICKET_UUID,
            'kilter' => self::KILTER, 'change_id' => 1, 'entered_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'status' => 'pending', 'attempts' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $id;
    }
}
