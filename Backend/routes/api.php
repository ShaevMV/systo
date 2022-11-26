<?php

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
        [\App\Http\Controllers\Festival\OrderingTicketsController::class, 'getInfoForOrder']);
    Route::get('/findPromoCode/{promoCode}',
        [\App\Http\Controllers\Festival\OrderingTicketsController::class, 'findPromoCode']);

    Route::post('/ticketsOrder/create', [\App\Http\Controllers\TicketsOrder\OrderTickets::class, 'create']);
});


Route::controller(\App\Http\Controllers\AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});
