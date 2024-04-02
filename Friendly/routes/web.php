<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\TicketController;
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


Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return redirect('/');
})->name('dashboard');


Route::get('/register', function () {
    return redirect('/');
});

Route::middleware(['auth', 'verified'])->get('/', [TicketController::class, 'view'])->name('viewAddTickets');
Route::middleware(['auth', 'verified'])->post('/', [TicketController::class, 'add'])->name('addTickets');

Route::middleware(['auth', 'verified'])->get('/live', [TicketController::class, 'viewLive'])->name('viewLiveTickets');
Route::middleware(['auth', 'verified'])->post('/live', [TicketController::class, 'addLiveTicket'])->name('addLiveTicket');

Route::middleware(['auth', 'verified'])->get('/list', [TicketController::class, 'viewList'])->name('viewListTickets');
Route::middleware(['auth', 'verified'])->post('/list', [TicketController::class, 'addListTicket'])->name('addListTicket');


Route::middleware(['admin', 'verified'])->get('/admin', [AdminController::class, 'view'])->name('adminView');

Route::middleware(['admin', 'verified'])->get('/admin/user/{festival_id}', [AdminController::class, 'users'])->name('adminUser');
Route::middleware(['admin', 'verified'])->get('/admin/user/edit/{id}', [AdminController::class, 'editUser'])->name('editUser');
Route::middleware(['admin', 'verified'])->post('/admin/user', [AdminController::class, 'delUser'])->name('delUser');
Route::middleware(['admin', 'verified'])->get('/admin/user_create', [AdminController::class, 'createUser'])->name('createUser');
Route::middleware(['admin', 'verified'])->post('/admin/user/create', [AdminController::class, 'registerUser'])->name('registerUser');


Route::middleware(['admin', 'verified'])->get('/admin/tickets', [TicketController::class, 'tickets'])->name('adminTickets');
Route::middleware(['admin', 'verified'])->post('/admin/tickets', [TicketController::class, 'delTicket'])->name('delTicket');
Route::middleware(['admin', 'verified'])->get('/admin/tickets/{id}', [TicketController::class, 'getPdf'])->name('getPdf');

Route::get('/clear', function() {
    Artisan::call('config:cache');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    exec('rm -f ' . storage_path('logs/.log'));
    exec('rm -f ' . base_path('.log'));
    return "Cache is cleared";
})->name('clear.cache');
