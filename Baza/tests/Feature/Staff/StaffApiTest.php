<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Models\User;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Database\Seeders\BazaRolePermissionsSeeder;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Шаг 5: регистрация персонала из PWA — /api/staff (GET/POST). Доступ — staff.manage
 * (administrator по дефолту). БД baza_test (phpunit.xml).
 */
class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/staff';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->seed(ChangesTestDataSeeder::class);     // admin id=1
        $this->seed(BazaRolePermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $u = User::factory()->create();
        DB::table('users')->where('id', $u->id)->update(['role' => $role, 'is_admin' => false]);

        return User::find($u->id);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::URL)->assertUnauthorized();
    }

    public function test_admin_lists_staff(): void
    {
        $res = $this->actingAs(User::find(1))->getJson(self::URL)->assertOk()->assertJson(['success' => true]);
        self::assertNotEmpty($res->json('staff'));
        self::assertNotEmpty($res->json('roles'));
    }

    public function test_non_staff_manager_forbidden(): void
    {
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))->getJson(self::URL)->assertStatus(403);
        $this->actingAs($this->userWithRole(ShiftRole::TICKETER))
            ->postJson(self::URL, ['name' => 'X', 'email' => 'x@y.ru', 'password' => 'secret1'])
            ->assertStatus(403);
    }

    public function test_admin_creates_staff_with_hashed_password(): void
    {
        $this->actingAs(User::find(1))->postJson(self::URL, [
            'name' => 'Новый Билетёр',
            'email' => 'newbie@spaceofjoy.ru',
            'password' => 'secret1',
            'role' => ShiftRole::GUARD,
        ])->assertOk()->assertJson(['success' => true]);

        $u = User::where('email', 'newbie@spaceofjoy.ru')->first();
        self::assertNotNull($u);
        self::assertSame(ShiftRole::GUARD, $u->role);
        self::assertNotSame('secret1', $u->password, 'пароль должен быть захэширован');
        self::assertTrue(Hash::check('secret1', $u->password));
    }

    public function test_create_is_idempotent_by_email(): void
    {
        $payload = ['name' => 'Дубль', 'email' => 'dub@spaceofjoy.ru', 'password' => 'secret1', 'role' => ShiftRole::TICKETER];

        $this->actingAs(User::find(1))->postJson(self::URL, $payload)->assertOk();
        $this->actingAs(User::find(1))->postJson(self::URL, array_merge($payload, ['name' => 'Дубль 2']))->assertOk();

        self::assertSame(1, User::where('email', 'dub@spaceofjoy.ru')->count());
        self::assertSame('Дубль 2', User::where('email', 'dub@spaceofjoy.ru')->value('name'));
    }

    public function test_validation_rejects_bad_input(): void
    {
        $this->actingAs(User::find(1))->postJson(self::URL, ['name' => 'No Email', 'password' => 'secret1'])
            ->assertStatus(422);
        $this->actingAs(User::find(1))->postJson(self::URL, ['name' => 'Bad role', 'email' => 'b@b.ru', 'password' => 'secret1', 'role' => 'king'])
            ->assertStatus(422);
    }
}
