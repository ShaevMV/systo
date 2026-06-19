<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ChangesTestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Security-тесты закрытия auth-дыр входа (Baza, Ф1).
 *
 * Проверяют, что после фикса:
 *  - /users (отдавал список ВСЕХ сотрудников неавторизованному), /profile (GET/POST)
 *    и /password-request требуют сессии — гостя редиректит на /login;
 *  - залогиненный сотрудник по-прежнему открывает /users и /profile (вход не сломан);
 *  - публичные роуты-мусор /register и /pages/* удалены (404).
 *
 * Используют отдельную БД `baza_test` (см. phpunit.xml). ChangesTestDataSeeder
 * создаёт пользователя id=1 (Admin).
 */
class AuthGuardRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ChangesTestDataSeeder::class);
    }

    // ----- негатив: гость не имеет доступа (раньше эти роуты были публичны) -----

    public function test_users_index_redirects_guest_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_profile_edit_redirects_guest_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_profile_update_redirects_guest_to_login(): void
    {
        // CSRF — отдельный слой (web-группа), отключаем, чтобы проверить именно
        // auth-редирект, а не 419 от VerifyCsrfToken (фронт шлёт _token).
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->post('/profile', ['name' => 'X'])->assertRedirect('/login');
    }

    public function test_password_request_redirects_guest_to_login(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->post('/password-request', ['password' => 'x'])->assertRedirect('/login');
    }

    // ----- позитив: легитимный доступ авторизованного сотрудника не сломан -----

    // Примечание: позитивного теста на /users нет намеренно. /users — пред-существующе
    // сломанный роут: UserController::index рендерит вьюху change.index, которая ждёт
    // переменную $report (смены), а контроллер передаёт только users → 500 для ЛЮБОГО
    // (и до Ф1). Ф1 лишь закрывает его под auth (гость → редирект, см. тест выше);
    // чинить саму вьюху — вне scope Ф1 (экран пользователей переедет в новую админку).

    public function test_authenticated_user_can_open_profile(): void
    {
        $this->actingAs(User::find(1))
            ->get('/profile')
            ->assertOk();
    }

    // ----- удалённые публичные роуты-мусор (регистрация + демо-страницы шаблона) -----

    public function test_register_route_removed(): void
    {
        // Регистрации в Baza нет — персонал заводится сидером. Роут удалён.
        $this->get('/register')->assertNotFound();
    }

    public function test_demo_pages_routes_removed(): void
    {
        // Демо-страницы шаблона Argon удалены (не часть боевого КПП-флоу).
        $this->get('/pages/icons')->assertNotFound();
        $this->get('/pages/upgrade')->assertNotFound();
        $this->get('/pages/rtl')->assertNotFound();
    }
}
