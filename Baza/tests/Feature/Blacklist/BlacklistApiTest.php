<?php

declare(strict_types=1);

namespace Tests\Feature\Blacklist;

use App\Models\BlacklistModel;
use App\Models\User;
use Baza\Shared\Services\DefineService;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Чёрный список отозванных билетов (Ф5, PR-6, B6).
 *   - GET /api/blacklist — синк на телефон (web-auth, без ПДн).
 *   - POST /api/baza/ingest/revoke — приём отзыва от org (S2S, X-Baza-Token).
 *   - /api/enter блокирует отозванный билет (defense-in-depth).
 * БД baza_test (phpunit.xml).
 */
class BlacklistApiTest extends TestCase
{
    use RefreshDatabase;

    private const READ = '/api/blacklist';

    private const REVOKE = '/api/baza/ingest/revoke';

    private const TOKEN = 'test-baza-ingest-token';

    private const FESTIVAL = ChangesTestDataSeeder::FESTIVAL_ID;

    private const KILTER = 901;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.baza_ingest.tokens' => [self::TOKEN]]);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);
    }

    /** @return array<string,string> */
    private function token(): array
    {
        return ['X-Baza-Token' => self::TOKEN];
    }

    private function createElTicket(int $kilter): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => $kilter,
            'uuid' => '11111111-1111-1111-1111-111111111111',
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
            'festival_id' => self::FESTIVAL,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_read_requires_authentication(): void
    {
        $this->getJson(self::READ)->assertUnauthorized();
    }

    public function test_revoke_requires_token(): void
    {
        $this->postJson(self::REVOKE, ['ticket_uuid' => 'x'])->assertStatus(401);
    }

    public function test_revoke_needs_identifier(): void
    {
        $this->postJson(self::REVOKE, ['reason' => 'возврат'], $this->token())->assertStatus(422);
    }

    public function test_revoke_then_appears_in_blacklist_without_pii(): void
    {
        $uuid = '99999999-9999-4999-8999-999999999999';
        $this->postJson(self::REVOKE, [
            'ticket_uuid' => $uuid,
            'kilter' => self::KILTER,
            'festival_id' => self::FESTIVAL,
            'reason' => 'возврат',
        ], $this->token())->assertStatus(200)->assertJson(['success' => true]);

        $res = $this->actingAs(User::find(1))->getJson(self::READ.'?festival_id='.self::FESTIVAL)
            ->assertOk()
            ->assertJson(['success' => true]);

        self::assertSame(1, $res->json('count'));
        $item = $res->json('items.0');
        self::assertSame($uuid, $item['uuid']);
        self::assertSame(self::KILTER, $item['kilter']);
        // Без ПДн
        foreach (['name', 'phone', 'email', 'fio'] as $forbidden) {
            self::assertArrayNotHasKey($forbidden, $item);
        }
    }

    public function test_revoke_is_idempotent(): void
    {
        $uuid = '88888888-8888-4888-8888-888888888888';
        $payload = ['ticket_uuid' => $uuid, 'festival_id' => self::FESTIVAL];

        $this->postJson(self::REVOKE, $payload, $this->token())->assertOk();
        $this->postJson(self::REVOKE, $payload + ['reason' => 'отмена'], $this->token())->assertOk();

        self::assertSame(1, BlacklistModel::where('ticket_uuid', $uuid)->count());
    }

    public function test_enter_blocked_for_revoked_ticket(): void
    {
        $this->createElTicket(self::KILTER);
        $this->postJson(self::REVOKE, ['kilter' => self::KILTER, 'festival_id' => self::FESTIVAL], $this->token())->assertOk();

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', ['type' => DefineService::ELECTRON_TICKET, 'id' => self::KILTER])
            ->assertStatus(422);

        // Билет НЕ должен быть помечен впущенным.
        self::assertNull(\App\Models\ElTicketsModel::whereKilter(self::KILTER)->first()->date_change);
    }
}
