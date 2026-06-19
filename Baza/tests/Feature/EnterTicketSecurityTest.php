<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ChangesModel;
use App\Models\ElTicketsModel;
use App\Models\User;
use Baza\Shared\Services\DefineService;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Security-тесты впуска на КПП (Baza, фикс A2).
 *
 * Проверяют, что после фикса:
 *  - POST /api/scan и /api/enter требуют аутентификации (раньше были без auth);
 *  - смена берётся по залогиненному сотруднику (Auth::id()), а НЕ по user_id из тела;
 *  - повторный впуск того же билета отклоняется и НЕ накручивает счётчик смены.
 *
 * Используют отдельную БД `baza_test` (см. phpunit.xml). ChangesTestDataSeeder
 * создаёт пользователя id=1 (Admin) и одну открытую смену id=1 (festival_id = FESTIVAL_ID,
 * count_el_tickets = 5).
 */
class EnterTicketSecurityTest extends TestCase
{
    use RefreshDatabase;

    private const KILTER = 770077;
    private const FESTIVAL_ID = ChangesTestDataSeeder::FESTIVAL_ID;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF — отдельный слой защиты (фронт шлёт _token). В этих тестах проверяем
        // именно auth + защиту от двойного впуска, поэтому CSRF отключаем, чтобы он не
        // перехватывал запрос раньше auth-middleware (иначе вернётся 419 вместо 401).
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->seed(ChangesTestDataSeeder::class);
    }

    /**
     * Создаёт электронный билет того же фестиваля, что и открытая смена,
     * ещё не пропущенный (date_change = null).
     */
    private function createElTicket(string $status = 'paid'): void
    {
        DB::table('el_tickets')->insert([
            'kilter'      => self::KILTER,
            'uuid'        => '11111111-1111-1111-1111-111111111111',
            'city'        => 'Москва',
            'name'        => 'Тест Гость',
            'email'       => 'guest@example.com',
            'phone'       => '+70000000000',
            'date_order'  => now(),
            'status'      => $status,
            'type_ticket' => 'Электронный',
            'type_ticket_id' => '22222222-2222-2222-2222-222222222222',
            'is_need_seedling' => 0,
            'change_id'   => null,
            'date_change' => null,
            'festival_id' => self::FESTIVAL_ID,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function test_scan_requires_authentication(): void
    {
        $this->postJson('/api/scan', ['search' => 'https://org.spaceofjoy.ru/newTickets/x'])
            ->assertUnauthorized();
    }

    public function test_enter_requires_authentication(): void
    {
        $this->createElTicket();

        $this->postJson('/api/enter', [
            'type' => DefineService::ELECTRON_TICKET,
            'id'   => self::KILTER,
        ])->assertUnauthorized();

        // Билет не должен быть помечен впущенным неавторизованным запросом.
        self::assertNull(ElTicketsModel::whereKilter(self::KILTER)->first()->date_change);
    }

    public function test_authenticated_enter_marks_ticket_and_increments_report_once(): void
    {
        $this->createElTicket();
        // Берём смену динамически: MySQL не откатывает auto-increment между тестами,
        // поэтому id смены из сидера не обязательно равен 1.
        $change = ChangesModel::query()->first();
        $before = $change->count_el_tickets;

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', [
                'type' => DefineService::ELECTRON_TICKET,
                'id'   => self::KILTER,
            ])
            ->assertOk();

        $ticket = ElTicketsModel::whereKilter(self::KILTER)->first();
        self::assertNotNull($ticket->date_change, 'Билет должен быть помечен впущенным');
        self::assertSame($change->id, (int) $ticket->change_id, 'change_id = id открытой смены сотрудника');
        self::assertSame($before + 1, ChangesModel::find($change->id)->count_el_tickets, 'Счётчик смены +1');
    }

    public function test_double_entry_is_rejected_and_report_not_inflated(): void
    {
        $this->createElTicket();
        $change = ChangesModel::query()->first();

        $this->actingAs(User::find(1))
            ->postJson('/api/enter', [
                'type' => DefineService::ELECTRON_TICKET,
                'id'   => self::KILTER,
            ])
            ->assertOk();

        $afterFirst = ChangesModel::find($change->id)->count_el_tickets;

        // Повторный впуск того же билета — отклонён, счётчик НЕ растёт.
        $this->actingAs(User::find(1))
            ->postJson('/api/enter', [
                'type' => DefineService::ELECTRON_TICKET,
                'id'   => self::KILTER,
            ])
            ->assertStatus(422);

        self::assertSame(
            $afterFirst,
            ChangesModel::find($change->id)->count_el_tickets,
            'Повторный впуск не должен накручивать счётчик смены'
        );
    }

    public function test_enter_uses_session_user_not_body_user_id(): void
    {
        $this->createElTicket();

        // В теле подсунут несуществующий user_id=999 (у него нет открытой смены).
        // Если бы сервер доверял телу — getCurrentChanges бросил бы «смена не найдена» (422).
        // Должен использоваться Auth::id() = 1 → впуск успешен.
        $this->actingAs(User::find(1))
            ->postJson('/api/enter', [
                'type'    => DefineService::ELECTRON_TICKET,
                'id'      => self::KILTER,
                'user_id' => 999,
            ])
            ->assertOk();
    }
}
