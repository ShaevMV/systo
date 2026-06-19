<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\StaffUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Тесты заведения персонала КПП из gitignored-файла (Baza, Ф1).
 *
 * Проверяют, что:
 *  - сидер/команда заводят сотрудника с РАБОЧИМ (хешированным) паролем;
 *  - повторный прогон идемпотентен (нет дублей — раньше insert() падал);
 *  - пароль в БД хеширован, а не лежит открытым текстом;
 *  - флаг is_admin выставляется (хотя is_admin вне $fillable);
 *  - отсутствие файла-списка не роняет сидер.
 *
 * Безопасность (must-fix): путь к списку подменяется на ВРЕМЕННЫЙ файл в
 * sys_get_temp_dir() через config('baza.staff_users_path') — тест НИКОГДА не
 * пишет в боевой database/seeders/data/staff_users.php (нет риска утечки
 * тестовых паролей в боевой путь и гонки при параллельных прогонах).
 */
class StaffUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    private ?string $tmpFile = null;

    protected function tearDown(): void
    {
        if ($this->tmpFile !== null && is_file($this->tmpFile)) {
            @unlink($this->tmpFile);
        }
        $this->tmpFile = null;

        parent::tearDown();
    }

    /**
     * Кладёт список во ВРЕМЕННЫЙ файл и направляет на него config.
     *
     * @param array<int, array{0:string, 1:string, 2:string, 3?:bool}> $rows
     */
    private function useStaffFile(array $rows): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'staff_users_');
        file_put_contents($this->tmpFile, '<?php return '.var_export($rows, true).';');
        config(['baza.staff_users_path' => $this->tmpFile]);
    }

    public function test_seeder_creates_user_with_working_password(): void
    {
        $this->useStaffFile([['t1@test.local', 'Тест 1', 'pass-123', false]]);

        $this->seed(StaffUsersSeeder::class);

        self::assertTrue(
            Auth::attempt(['email' => 't1@test.local', 'password' => 'pass-123']),
            'Заведённый пароль должен реально работать (хеш корректен)'
        );
    }

    public function test_seeder_is_idempotent_on_repeat(): void
    {
        $this->useStaffFile([['dup@test.local', 'Дубль', 'p1', false]]);

        // Раньше createList делал insert() → второй прогон упал бы на дубле email.
        $this->seed(StaffUsersSeeder::class);
        $this->seed(StaffUsersSeeder::class);

        self::assertSame(1, User::where('email', 'dup@test.local')->count());
    }

    public function test_seeded_password_is_hashed_not_plaintext(): void
    {
        $this->useStaffFile([['hash@test.local', 'Хеш', 'pl41n-s3cret', false]]);

        $this->seed(StaffUsersSeeder::class);

        $user = User::where('email', 'hash@test.local')->first();
        self::assertNotNull($user);
        self::assertNotSame('pl41n-s3cret', $user->password, 'Пароль не должен лежать открытым текстом');
        self::assertTrue(Hash::check('pl41n-s3cret', $user->password), 'В БД корректный хеш пароля');
    }

    public function test_seeder_sets_is_admin_flag(): void
    {
        $this->useStaffFile([
            ['adm@test.local', 'Админ', 'p', true],
            ['usr@test.local', 'Билетёр', 'p', false],
        ]);

        $this->seed(StaffUsersSeeder::class);

        self::assertTrue((bool) User::where('email', 'adm@test.local')->first()->is_admin);
        self::assertFalse((bool) User::where('email', 'usr@test.local')->first()->is_admin);
    }

    public function test_seeder_missing_file_does_not_throw(): void
    {
        config(['baza.staff_users_path' => sys_get_temp_dir().'/nope_'.uniqid().'.php']);

        // Не должно бросать исключение — только warn и выход.
        $this->seed(StaffUsersSeeder::class);

        self::assertSame(0, User::count(), 'Без файла-списка персонал не заводится');
    }

    public function test_command_creates_staff_from_file(): void
    {
        $this->useStaffFile([['cmd@test.local', 'Команда', 'cmd-pass', true]]);

        $this->artisan('tickets:crateUser')->assertSuccessful();

        self::assertTrue(Auth::attempt(['email' => 'cmd@test.local', 'password' => 'cmd-pass']));
        self::assertTrue((bool) User::where('email', 'cmd@test.local')->first()->is_admin);
    }

    public function test_command_fails_without_file(): void
    {
        config(['baza.staff_users_path' => sys_get_temp_dir().'/nope_'.uniqid().'.php']);

        $this->artisan('tickets:crateUser')->assertFailed();
    }
}
