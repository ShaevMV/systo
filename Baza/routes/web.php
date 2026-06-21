<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Changes\ChangesController;
use App\Http\Controllers\Permission\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sync\SyncController;
use App\Http\Controllers\Tickets\ScanController;
use App\Http\Controllers\Tickets\SearchController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Auth

// Login
Route::get('/login', [LoginController::class, 'loginPage'])->name('login-page');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//Home
Route::get('/', [ScanController::class, 'scanPage'])->name('home');

// Профиль, список пользователей и смена пароля — только для авторизованных
// сотрудников (Ф1). Раньше /profile (GET/POST) и /users были публичны, а
// /users отдавал список ВСЕХ сотрудников неавторизованному (UserController::index).
Route::middleware('auth')->group(function () {
    // Смена пароля авторизованным сотрудником (форма внутри /profile, требует старый пароль)
    Route::post('/password-request', [ProfileController::class, 'password'])->name('profile.password');

    //Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    //User
    Route::get('/users', [UserController::class, 'index'])->name('user.index');
});


// Scan
Route::get('/scan', [ScanController::class, 'scanPage'])->name('tickets.scan');
Route::get('/search', [SearchController::class, 'searchPage'])->name('tickets.search');
Route::post('/enterForTable', [SearchController::class, 'enterForTable'])->name('tickets.scan.enterForTable');

// Сканирование QR и впуск гостя (AJAX из scan.blade.php). Перенесено из routes/api.php
// под middleware('auth'): web-группа стартует сессию + проверяет CSRF (_token уже шлёт фронт),
// поэтому id сотрудника берётся из сессии (Auth::id()), а не из тела запроса.
Route::middleware('auth')->group(function () {
    Route::post('/api/scan', [\App\Http\Controllers\Api\ScanController::class, 'search'])->name('tickets.scan.search');
    Route::post('/api/enter', [\App\Http\Controllers\Api\ScanController::class, 'enter'])->name('tickets.scan.enter');

    // Офлайн-снимок билетов для PWA-сканера (Ф5, PR-3). GET → CSRF не требуется.
    // Сессионная auth: снимок содержит ФИО гостей, отдаём только сотруднику КПП.
    Route::get('/api/snapshot', [\App\Http\Controllers\Api\SnapshotController::class, 'index'])->name('tickets.snapshot');

    // JSON-поиск без QR для PWA (Ф5, PR-5). GET → CSRF не требуется. Тот же SearchService,
    // что Blade /search; содержит ПДн → только сотруднику КПП.
    Route::get('/api/search', [\App\Http\Controllers\Api\SearchController::class, 'search'])->name('tickets.search.api');

    // Синк чёрного списка отозванных билетов (Ф5, PR-6, B6). GET → CSRF не требуется.
    Route::get('/api/blacklist', [\App\Http\Controllers\Api\BlacklistController::class, 'index'])->name('tickets.blacklist');

    // Дренаж офлайн-намерений впуска в append-only журнал (Ф5, PR-8, гейт мульти-устройства).
    Route::post('/api/entry-events', [\App\Http\Controllers\Api\EntryEventsController::class, 'store'])->name('tickets.entry-events');

    // «Кто я» для PWA (роль + права) — гейтинг меню + признак полной карточки (Шаг 3).
    Route::get('/api/whoami', [\App\Http\Controllers\Api\WhoamiController::class, 'index'])->name('whoami');

    // Личное расписание сотрудника (PR-A). Доступ — только 'auth' (без shift.compose):
    // рядовой охранник/билетёр видит СВОИ плановые смены. GET → CSRF не требуется.
    Route::get('/api/my-schedule', [\App\Http\Controllers\Api\MyScheduleController::class, 'index'])->name('api.my-schedule');
});

// Матрица прав «роль×действие» в новом PWA (Шаг 4). GET — CSRF не нужен; POST — X-XSRF-TOKEN.
// Доступ только с правом rbac.manage (administrator по дефолту).
Route::middleware(['auth', 'permission:rbac.manage'])->group(function () {
    Route::get('/api/permissions/matrix', [\App\Http\Controllers\Api\PermissionController::class, 'matrix'])->name('api.permissions.matrix');
    Route::post('/api/permissions/matrix', [\App\Http\Controllers\Api\PermissionController::class, 'save'])->name('api.permissions.save');
});

// Регистрация персонала из нового PWA (Шаг 5). Доступ — право staff.manage (administrator по дефолту).
Route::middleware(['auth', 'permission:staff.manage'])->group(function () {
    Route::get('/api/staff', [\App\Http\Controllers\Api\StaffController::class, 'index'])->name('api.staff.list');
    Route::post('/api/staff', [\App\Http\Controllers\Api\StaffController::class, 'store'])->name('api.staff.store');
});

// Управление сменами из нового PWA (Шаг 6). Список/создание/состав — shift.compose;
// закрытие — shift.close. Изоляция начальника — внутри контроллера (видит только свою смену).
Route::middleware(['auth', 'permission:shift.compose'])->group(function () {
    Route::get('/api/shifts', [\App\Http\Controllers\Api\ShiftController::class, 'index'])->name('api.shifts.list');
    Route::get('/api/shifts/users', [\App\Http\Controllers\Api\ShiftController::class, 'users'])->name('api.shifts.users');
    Route::post('/api/shifts', [\App\Http\Controllers\Api\ShiftController::class, 'store'])->name('api.shifts.store');
});
Route::middleware(['auth', 'permission:shift.close'])->group(function () {
    Route::post('/api/shifts/{id}/close', [\App\Http\Controllers\Api\ShiftController::class, 'close'])->name('api.shifts.close');
});

// Плановое расписание смен (PR-A). Составление сетки заранее — право shift.compose.
// Изоляция начальника (своя плановая смена) — внутри контроллера. GET — CSRF не нужен; POST/PUT — X-XSRF-TOKEN.
Route::middleware(['auth', 'permission:shift.compose'])->group(function () {
    Route::get('/api/schedules', [\App\Http\Controllers\Api\ShiftScheduleController::class, 'index'])->name('api.schedules.list');
    Route::post('/api/schedules', [\App\Http\Controllers\Api\ShiftScheduleController::class, 'store'])->name('api.schedules.store');
    Route::put('/api/schedules/{id}', [\App\Http\Controllers\Api\ShiftScheduleController::class, 'update'])->name('api.schedules.update');
    Route::post('/api/schedules/{id}/cancel', [\App\Http\Controllers\Api\ShiftScheduleController::class, 'cancel'])->name('api.schedules.cancel');
});

// changes — RBAC по матрице прав (Ф2): 'auth' (гость → login) + 'permission:<действие>'.
// administrator проходит везде (суперроль), is_admin-юзеры не теряют доступ.
Route::get('/report', [ChangesController::class, 'report'])->name('changes.report')->middleware(['auth', 'permission:report.view']);
Route::get('/change/edit/{id?}', [ChangesController::class, 'viewAddChange'])->name('changes.edit')->middleware(['auth', 'permission:shift.compose']);
Route::post('/change/close', [ChangesController::class, 'close'])->name('changes.close')->middleware(['auth', 'permission:shift.close']);
Route::post('/change/save', [ChangesController::class, 'save'])->name('changes.save')->middleware(['auth', 'permission:shift.compose']);
Route::post('/change/remove', [ChangesController::class, 'remove'])->name('changes.remove')->middleware(['auth', 'permission:shift.remove']);

// sync — только право sync.manage (в дефолте — лишь administrator)
Route::get('/sync', [SyncController::class, 'index'])->name('sync.index')->middleware(['auth', 'permission:sync.manage']);
Route::post('/sync/export', [SyncController::class, 'export'])->name('sync.export')->middleware(['auth', 'permission:sync.manage']);
Route::post('/sync/import', [SyncController::class, 'import'])->name('sync.import')->middleware(['auth', 'permission:sync.manage']);

// Права доступа — редактор матрицы роль×действие (Ф2). Только право rbac.manage
// (по дефолту лишь administrator). administrator не редактируется (суперроль).
Route::get('/permissions', [PermissionController::class, 'index'])->name('permission.index')->middleware(['auth', 'permission:rbac.manage']);
Route::post('/permissions', [PermissionController::class, 'save'])->name('permission.save')->middleware(['auth', 'permission:rbac.manage']);
