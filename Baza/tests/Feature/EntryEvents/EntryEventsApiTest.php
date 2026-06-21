<?php

declare(strict_types=1);

namespace Tests\Feature\EntryEvents;

use App\Models\ChangesModel;
use App\Models\ElTicketsModel;
use App\Models\EntryEventModel;
use App\Models\User;
use Baza\Shared\Services\DefineService;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Дренаж офлайн-намерений в append-only журнал (Ф5, PR-8) — гейт мульти-устройства.
 * POST /api/entry-events. БД baza_test (phpunit.xml).
 */
class EntryEventsApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/entry-events';

    private const FESTIVAL = ChangesTestDataSeeder::FESTIVAL_ID;

    private const KILTER = 555;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);
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

    public function test_requires_authentication(): void
    {
        $this->postJson(self::URL, ['events' => []])->assertUnauthorized();
    }

    public function test_first_entry_wins_second_is_duplicate(): void
    {
        $this->createElTicket(self::KILTER);
        $change = ChangesModel::query()->first();
        $before = $change->count_el_tickets;

        // Два намерения по одному билету (как с двух устройств) в одном дренаже.
        $res = $this->actingAs(User::find(1))->postJson(self::URL, [
            'events' => [
                ['client_op_id' => 'op-A', 'type' => DefineService::ELECTRON_TICKET, 'kilter' => self::KILTER, 'device_id' => 'devA'],
                ['client_op_id' => 'op-B', 'type' => DefineService::ELECTRON_TICKET, 'kilter' => self::KILTER, 'device_id' => 'devB'],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $statuses = collect($res->json('results'))->pluck('status', 'client_op_id');
        self::assertSame('entered', $statuses['op-A']);
        self::assertSame('duplicate', $statuses['op-B']);

        // Счётчик смены вырос РОВНО на 1 (второй впуск не засчитан).
        self::assertSame($before + 1, ChangesModel::find($change->id)->count_el_tickets);
        // В журнале обе записи (append-only).
        self::assertSame(2, EntryEventModel::count());
        self::assertNotNull(ElTicketsModel::whereKilter(self::KILTER)->first()->date_change);
    }

    public function test_idempotent_by_client_op_id(): void
    {
        $this->createElTicket(self::KILTER);
        $change = ChangesModel::query()->first();

        $payload = ['events' => [['client_op_id' => 'op-X', 'type' => DefineService::ELECTRON_TICKET, 'kilter' => self::KILTER]]];

        $first = $this->actingAs(User::find(1))->postJson(self::URL, $payload)->assertOk();
        self::assertSame('entered', $first->json('results.0.status'));
        $afterFirst = ChangesModel::find($change->id)->count_el_tickets;

        // Повторный дренаж того же намерения — no-op (идемпотентность), счётчик не растёт.
        $second = $this->actingAs(User::find(1))->postJson(self::URL, $payload)->assertOk();
        self::assertSame('already', $second->json('results.0.status'));
        self::assertSame($afterFirst, ChangesModel::find($change->id)->count_el_tickets);
        self::assertSame(1, EntryEventModel::count());
    }

    public function test_revoked_ticket_not_entered(): void
    {
        $this->createElTicket(self::KILTER);
        \App\Models\BlacklistModel::query()->create([
            'ticket_uuid' => null, 'kilter' => self::KILTER, 'festival_id' => self::FESTIVAL, 'reason' => 'возврат',
        ]);

        $res = $this->actingAs(User::find(1))->postJson(self::URL, [
            'events' => [['client_op_id' => 'op-R', 'type' => DefineService::ELECTRON_TICKET, 'kilter' => self::KILTER]],
        ])->assertOk();

        self::assertSame('revoked', $res->json('results.0.status'));
        self::assertNull(ElTicketsModel::whereKilter(self::KILTER)->first()->date_change);
    }
}
