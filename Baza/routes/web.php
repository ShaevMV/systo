<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Changes\ChangesController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
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

// Register
Route::get('/register', [LoginController::class, 'registerPage'])->name('register');

// PasswordRequest
Route::get('/password-request', [LoginController::class, 'passwordRequestPage'])->name('password.request');
Route::post('/password-request', [LoginController::class, 'passwordRequestPage'])->name('profile.password');


//Home
Route::get('/', [ScanController::class, 'scanPage'])->name('home');

//Profile
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

//User
Route::get('/users', [UserController::class, 'index'])->name('user.index');


//Page
Route::get('/pages/icons', [PageController::class, 'icons'])->name('pages.icons');
Route::get('/pages/maps', [PageController::class, 'maps'])->name('pages.maps');
Route::get('/pages/tables', [PageController::class, 'tables'])->name('pages.tables');
Route::get('/pages/rtl', [PageController::class, 'rtl'])->name('pages.rtl');
Route::get('/pages/upgrade', [PageController::class, 'upgrade'])->name('pages.upgrade');
Route::get('/pages/typography', [PageController::class, 'typography'])->name('pages.typography');
Route::get('/pages/notifications', [PageController::class, 'notifications'])->name('pages.notifications');


// Scan
Route::get('/scan', [ScanController::class, 'scanPage'])->name('tickets.scan');
Route::get('/search', [SearchController::class, 'searchPage'])->name('tickets.search');
Route::post('/enterForTable', [SearchController::class, 'enterForTable'])->name('tickets.scan.enterForTable');

// changes
Route::get('/report', [ChangesController::class, 'report'])->name('changes.report')->middleware('admin');
Route::get('/change/add', [ChangesController::class, 'viewAddChange'])->name('changes.add')->middleware('admin');
Route::get('/change/edit/{id}', [ChangesController::class, 'report'])->name('changes.edit')->middleware('admin');
Route::post('/change/close', [ChangesController::class, 'report'])->name('changes.close')->middleware('admin');
Route::post('/change/save', [ChangesController::class, 'save'])->name('changes.save')->middleware('admin');
