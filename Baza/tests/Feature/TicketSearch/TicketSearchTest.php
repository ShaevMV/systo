<?php

declare(strict_types=1);

namespace Tests\Feature\TicketSearch;

use App\Models\TicketSearchModel;
use Baza\Sync\Repositories\SyncRepositoryInterface;
use Baza\Tickets\Applications\Search\SearchService;
use Baza\Tickets\Responses\SearchResponse;
use Carbon\Carbon;
use Database\Seeders\ChangesTestDataSeeder;
use Database\Seeders\TicketSearchTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Ф-rich: поисковый индекс ticket_search — наполнение из ingest + поиск по всем полям
 * (ручной поиск на КПП без QR). БД baza_test.
 */
class TicketSearchTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-baza-ingest-token';

    private const INGEST_URL = '/api/baza/ingest/ticket';

    private const FESTIVAL_ID = ChangesTestDataSeeder::FESTIVAL_ID;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.baza_ingest.tokens' => [self::TOKEN]]);
    }

    /** @return array<string,string> */
    private function auth(): array
    {
        return ['X-Baza-Token' => self::TOKEN];
    }

    private function elTicket(string $uuid, int $kilter): array
    {
        return [
            'uuid' => $uuid, 'kilter' => $kilter, 'city' => 'Казань',
            'name' => 'Иван Петров', 'email' => 'ivan@example.com', 'phone' => '+79991234567',
            'date_order' => Carbon::now()->toIso8601String(), 'status' => 'paid',
            'festival_id' => self::FESTIVAL_ID, 'type_ticket' => 'Оргвзнос',
        ];
    }

    public function test_ingest_with_search_block_populates_rich_index(): void
    {
        $uuid = 'bbb20001-0000-4000-8000-000000000001';
        $this->postJson(self::INGEST_URL, [
            'target' => 'el_tickets',
            'ticket' => $this->elTicket($uuid, 800001),
            'search' => [
                'fio' => 'Иван Петров', 'phone' => '+79991234567', 'telegram' => 'ivan',
                'email' => 'ivan@example.com', 'city' => 'Казань',
                'external_order_no' => '123', 'type_ticket' => 'Оргвзнос',
            ],
        ], $this->auth())->assertStatus(200)->assertJson(['success' => true]);

        $row = TicketSearchModel::query()->where('ticket_uuid', $uuid)->first();
        self::assertNotNull($row);
        self::assertSame('electron', $row->type);
        self::assertSame('ivan', $row->telegram);
        self::assertSame(800001, (int) $row->kilter);
        self::assertSame('123', $row->external_order_no);
    }

    public function test_ingest_without_search_falls_back_to_ticket_fields(): void
    {
        $uuid = 'bbb20002-0000-4000-8000-000000000002';
        $this->postJson(self::INGEST_URL, [
            'target' => 'el_tickets',
            'ticket' => $this->elTicket($uuid, 800002),
        ], $this->auth())->assertStatus(200);

        $row = TicketSearchModel::query()->where('ticket_uuid', $uuid)->first();
        self::assertNotNull($row);
        self::assertSame('Иван Петров', $row->fio);   // name → fio
        self::assertSame('+79991234567', $row->phone);
        self::assertSame('Казань', $row->city);
    }

    public function test_ingest_is_idempotent_by_uuid(): void
    {
        $uuid = 'bbb20003-0000-4000-8000-000000000003';
        $body = ['target' => 'el_tickets', 'ticket' => $this->elTicket($uuid, 800003)];

        $this->postJson(self::INGEST_URL, $body, $this->auth())->assertStatus(200);
        $body['search'] = ['fio' => 'Обновлённое Имя'];
        $this->postJson(self::INGEST_URL, $body, $this->auth())->assertStatus(200);

        self::assertSame(1, TicketSearchModel::query()->where('ticket_uuid', $uuid)->count());
        self::assertSame('Обновлённое Имя', TicketSearchModel::query()->where('ticket_uuid', $uuid)->value('fio'));
    }

    public function test_search_finds_by_rich_fields(): void
    {
        $this->seed(TicketSearchTestDataSeeder::class);
        $search = app(SearchService::class);

        // по ФИО
        $byName = $search->find('Петров')->toArray()[SearchResponse::TICKET_SEARCH] ?? [];
        self::assertNotEmpty($byName, 'поиск по ФИО должен найти');

        // по telegram
        $byTg = $search->find('ivan')->toArray()[SearchResponse::TICKET_SEARCH] ?? [];
        self::assertNotEmpty($byTg, 'поиск по telegram должен найти');

        // по госномеру
        $byCar = $search->find('А123БВ77')->toArray()[SearchResponse::TICKET_SEARCH] ?? [];
        self::assertNotEmpty($byCar, 'поиск по госномеру должен найти');
        self::assertSame('auto', $byCar[0]['type']);

        // по имени ребёнка
        $byChild = $search->find('Маша')->toArray()[SearchResponse::TICKET_SEARCH] ?? [];
        self::assertNotEmpty($byChild, 'поиск по имени ребёнка должен найти');
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(TicketSearchTestDataSeeder::class);
        $this->seed(TicketSearchTestDataSeeder::class);

        self::assertSame(5, TicketSearchModel::query()->count());
    }

    public function test_ticket_search_is_in_sync_tables(): void
    {
        self::assertContains('ticket_search', app(SyncRepositoryInterface::class)->getSyncTables());
    }
}
