<?php

declare(strict_types=1);

namespace Tests\Feature\Whoami;

use App\Models\User;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Шаг 3: /api/whoami (роль+права) + фильтр ПДн карточки билета по праву ticket.pii.
 * Полную карточку (телефон/email/коммент) видят administrator/начальник смены/комендант КПП.
 * БД baza_test (phpunit.xml).
 */
class WhoamiPiiTest extends TestCase
{
    use RefreshDatabase;

    private const F = ChangesTestDataSeeder::FESTIVAL_ID;

    private const UUID = '11111111-1111-1111-1111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);   // user id=1 = administrator
        $this->seed(BazaRolePermissionsSeeder::class); // матрица + ticket.pii для chief/commandant
    }

    private function userWithRole(?string $role, bool $isAdmin = false): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => $isAdmin]);

        return User::find($u->id);
    }

    private function createElTicket(): void
    {
        DB::table('el_tickets')->insert([
            'kilter' => 7001,
            'uuid' => self::UUID,
            'city' => 'Москва',
            'name' => 'Тест Гость',
            'email' => 'guest@example.ru',
            'phone' => '+70000000000',
            'comment' => 'служебная заметка',
            'date_order' => now(),
            'status' => 'paid',
            'type_ticket' => 'Электронный',
            'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0,
            'change_id' => null,
            'date_change' => null,
            'festival_id' => self::F,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function scan(User $user): array
    {
        return $this->actingAs($user)
            ->postJson('/api/scan', ['search' => '/newTickets/' . self::UUID])
            ->assertOk()
            ->json();
    }

    public function test_whoami_requires_authentication(): void
    {
        $this->getJson('/api/whoami')->assertUnauthorized();
    }

    public function test_whoami_admin_can_view_pii(): void
    {
        $this->actingAs(User::find(1))->getJson('/api/whoami')
            ->assertOk()
            ->assertJson(['success' => true, 'is_admin' => true, 'role' => 'administrator', 'can_view_pii' => true]);
    }

    public function test_whoami_ticketer_cannot_view_pii(): void
    {
        $res = $this->actingAs($this->userWithRole('ticketer'))->getJson('/api/whoami')->assertOk();

        $res->assertJson(['role' => 'ticketer', 'can_view_pii' => false]);
        self::assertContains('ticket.scan', $res->json('permissions'));
        self::assertNotContains('ticket.pii', $res->json('permissions'));
    }

    public function test_scan_strips_pii_for_ticketer(): void
    {
        $this->createElTicket();
        $card = $this->scan($this->userWithRole('ticketer'));

        self::assertSame('Тест Гость', $card['name']);
        foreach (['phone', 'email', 'comment'] as $pii) {
            self::assertArrayNotHasKey($pii, $card, "ПДн {$pii} не должны отдаваться билетёру");
        }
    }

    public function test_scan_keeps_pii_for_admin(): void
    {
        $this->createElTicket();
        $card = $this->scan(User::find(1));

        self::assertSame('+70000000000', $card['phone']);
        self::assertSame('guest@example.ru', $card['email']);
        self::assertArrayHasKey('comment', $card);
    }

    public function test_scan_keeps_pii_for_shift_chief(): void
    {
        $this->createElTicket();
        $card = $this->scan($this->userWithRole('shift_chief'));

        self::assertArrayHasKey('phone', $card);
        self::assertArrayHasKey('email', $card);
    }
}
