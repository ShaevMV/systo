<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\TicketSearchModel;
use App\Models\User;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * JSON-поиск без QR для PWA-сканера (Ф5, PR-5): GET /api/search?q=.
 *
 * Сессионная auth персонала. Тот же SearchService::find, что Blade /search.
 * БД baza_test (phpunit.xml).
 */
class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/search';

    /** Совпадает с дефолтным фестивалем поиска (InMemoryMySqlTicketSearch::UUID_FESTIVAL). */
    private const FESTIVAL = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChangesTestDataSeeder::class); // сотрудник id=1
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL.'?q=Иван')->assertUnauthorized();
    }

    public function test_empty_query_returns_empty_groups(): void
    {
        $this->actingAs(User::find(1))->getJson(self::URL)
            ->assertOk()
            ->assertJson(['success' => true, 'groups' => []]);
    }

    public function test_finds_by_name_in_ticket_search(): void
    {
        TicketSearchModel::query()->create([
            'ticket_uuid' => '77777777-7777-4777-8777-777777777777',
            'festival_id' => self::FESTIVAL,
            'type' => 'electron',
            'kilter' => 501,
            'fio' => 'Иван Поисков',
            'type_ticket' => 'Оргвзнос',
        ]);

        $res = $this->actingAs(User::find(1))->getJson(self::URL.'?q=Поисков')
            ->assertOk()
            ->assertJson(['success' => true]);

        $group = $res->json('groups.ticket_search');
        self::assertNotEmpty($group);
        self::assertSame('Иван Поисков', $group[0]['fio']);
        self::assertSame(501, $group[0]['kilter']);
    }
}
