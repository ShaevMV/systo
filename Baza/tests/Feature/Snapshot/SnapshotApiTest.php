<?php

declare(strict_types=1);

namespace Tests\Feature\Snapshot;

use App\Models\TicketSearchModel;
use App\Models\User;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Офлайн-снимок билетов для PWA-сканера (Ф5, PR-3): GET /api/snapshot.
 *
 * Источник — индекс ticket_search. Сессионная auth персонала (как /api/scan).
 * Минимизация ПДн B5: отдаём только uuid/kilter/тип/цвет браслета/имя — НЕ телефон/email.
 * Дельта по updated_at (since), пагинация по id (after_id). БД baza_test (phpunit.xml).
 */
class SnapshotApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/snapshot';

    /** Совпадает с дефолтным фестивалем репозитория (InMemoryMySqlTicketSearch::UUID_FESTIVAL). */
    private const FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    private const OTHER_FESTIVAL = '00000000-0000-4000-8000-000000000abc';

    protected function setUp(): void
    {
        parent::setUp();

        // Создаёт сотрудника id=1 (для actingAs) + открытую смену.
        $this->seed(ChangesTestDataSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeRow(string $uuid, int $kilter, string $type = 'electron', array $extra = []): TicketSearchModel
    {
        return TicketSearchModel::query()->create(array_merge([
            'ticket_uuid' => $uuid,
            'festival_id' => self::FESTIVAL,
            'type' => $type,
            'kilter' => $kilter,
            'fio' => 'Гость '.$kilter,
            'phone' => '+7900000'.$kilter,
            'email' => 'g'.$kilter.'@example.com',
            'type_ticket' => 'Оргвзнос',
        ], $extra));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_returns_minimized_fields_and_color(): void
    {
        $this->makeRow('11111111-1111-4111-8111-111111111111', 101, 'electron');

        $res = $this->actingAs(User::find(1))->getJson(self::URL)
            ->assertOk()
            ->assertJson(['success' => true]);

        $item = $res->json('items.0');
        self::assertSame('11111111-1111-4111-8111-111111111111', $item['uuid']);
        self::assertSame(101, $item['kilter']);
        self::assertSame('electron', $item['type']);
        self::assertSame('green', $item['color']);            // цвет браслета по типу
        self::assertSame('Гость 101', $item['name']);
        // B5: персональные данные НЕ утекают в снимок
        self::assertArrayNotHasKey('phone', $item);
        self::assertArrayNotHasKey('email', $item);
        self::assertArrayNotHasKey('telegram', $item);
    }

    public function test_color_by_type(): void
    {
        $this->makeRow('22222222-2222-4222-8222-222222222222', 1, 'spisok');
        $this->makeRow('33333333-3333-4333-8333-333333333333', 2, 'auto');

        $items = $this->actingAs(User::find(1))->getJson(self::URL)->assertOk()->json('items');
        $byUuid = collect($items)->keyBy('uuid');

        self::assertSame('blue', $byUuid['22222222-2222-4222-8222-222222222222']['color']);
        self::assertSame('white', $byUuid['33333333-3333-4333-8333-333333333333']['color']);
    }

    public function test_filters_by_festival(): void
    {
        $this->makeRow('aaaaaaaa-1111-4111-8111-111111111111', 1, 'electron');
        $this->makeRow('bbbbbbbb-2222-4222-8222-222222222222', 2, 'electron', ['festival_id' => self::OTHER_FESTIVAL]);

        $res = $this->actingAs(User::find(1))->getJson(self::URL.'?festival_id='.self::FESTIVAL)->assertOk();
        self::assertSame(1, $res->json('count'));
        self::assertSame('aaaaaaaa-1111-4111-8111-111111111111', $res->json('items.0.uuid'));
    }

    public function test_pagination_by_after_id_and_has_more(): void
    {
        $this->makeRow('c0000001-0000-4000-8000-000000000001', 1);
        $this->makeRow('c0000002-0000-4000-8000-000000000002', 2);
        $this->makeRow('c0000003-0000-4000-8000-000000000003', 3);

        $res = $this->actingAs(User::find(1))->getJson(self::URL.'?limit=2')->assertOk();
        self::assertSame(2, $res->json('count'));
        self::assertTrue($res->json('has_more'));

        $next = $res->json('next_after_id');
        $res2 = $this->actingAs(User::find(1))->getJson(self::URL.'?limit=2&after_id='.$next)->assertOk();
        self::assertSame(1, $res2->json('count'));
        self::assertFalse($res2->json('has_more'));
    }

    public function test_delta_by_since_returns_only_changed(): void
    {
        $old = $this->makeRow('d0000001-0000-4000-8000-000000000001', 1);
        $new = $this->makeRow('d0000002-0000-4000-8000-000000000002', 2);

        DB::table('ticket_search')->where('id', $old->id)->update(['updated_at' => '2026-06-01 10:00:00']);
        DB::table('ticket_search')->where('id', $new->id)->update(['updated_at' => '2026-06-20 10:00:00']);

        $res = $this->actingAs(User::find(1))->getJson(self::URL.'?since=2026-06-10T00:00:00Z')->assertOk();
        self::assertSame(1, $res->json('count'));
        self::assertSame('d0000002-0000-4000-8000-000000000002', $res->json('items.0.uuid'));
    }

    public function test_bad_since_returns_full_snapshot_not_500(): void
    {
        $this->makeRow('e0000001-0000-4000-8000-000000000001', 1);

        $this->actingAs(User::find(1))->getJson(self::URL.'?since=notadate')
            ->assertOk()
            ->assertJson(['success' => true]);
    }
}
