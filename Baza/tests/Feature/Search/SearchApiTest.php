<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\TicketSearchModel;
use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        $this->seed(ChangesTestDataSeeder::class);     // сотрудник id=1 (admin)
        $this->seed(BazaRolePermissionsSeeder::class); // матрица: ticketer без ticket.pii
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    private function elWithPii(string $name): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => 8001, 'uuid' => '88888888-8888-4888-8888-888888888888',
            'city' => 'Москва', 'name' => $name, 'email' => 'guest@example.ru', 'phone' => '+70000000000',
            'comment' => 'служебная заметка', 'date_order' => now(), 'status' => 'paid',
            'type_ticket' => 'Электронный', 'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0, 'change_id' => null, 'date_change' => null,
            'festival_id' => self::FESTIVAL, 'created_at' => now(), 'updated_at' => now(),
        ]);
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

    public function test_search_strips_pii_for_ticketer(): void
    {
        $this->elWithPii('Иван Поисков');
        $res = $this->actingAs($this->userWithRole(ShiftRole::TICKETER))->getJson(self::URL.'?q=Поисков')->assertOk();

        $item = $res->json('groups.electron.0');
        self::assertNotNull($item);
        // ShowSearchWordService подсвечивает совпадение (<b>…</b>) — проверяем вхождение.
        self::assertStringContainsString('Поисков', $item['name']);
        foreach (['phone', 'email', 'comment'] as $pii) {
            self::assertArrayNotHasKey($pii, $item, "ПДн {$pii} не должны отдаваться билетёру в поиске");
        }
    }

    public function test_search_keeps_pii_for_admin(): void
    {
        $this->elWithPii('Иван Поисков');
        $item = $this->actingAs(User::find(1))->getJson(self::URL.'?q=Поисков')->assertOk()->json('groups.electron.0');

        self::assertSame('+70000000000', $item['phone']);
        self::assertSame('guest@example.ru', $item['email']);
        self::assertArrayHasKey('comment', $item);
    }
}
