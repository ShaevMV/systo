<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Festival\OrderingTicketsController;
use App\Http\Controllers\TicketsOrder\OrderTickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/festival')->group(static function (): void {
    Route::get('/orderingTickets',
        [OrderingTicketsController::class, 'getInfoForOrder']);
    Route::get('/findPromoCode/{promoCode}',
        [OrderingTicketsController::class, 'findPromoCode']);

    Route::post('/ticketsOrder/create', [OrderTickets::class, 'create']);
    Route::get('/ticketsOrder/getUserList', [OrderTickets::class, 'getUserList'])->middleware('auth:api');

    Route::get('/ticketsOrder/getItem/{id}', [OrderTickets::class, 'getOrderItem']);
});


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});
