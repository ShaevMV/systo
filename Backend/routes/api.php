<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Festival\OrderingTicketsController;
use App\Http\Controllers\TicketsOrder\Comment;
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
    Route::post('/ticketsOrder/getList', [OrderTickets::class, 'getList'])->middleware('auth:api')
        ->middleware('admin');

    Route::get('/ticketsOrder/getItem/{id}', [OrderTickets::class, 'getOrderItem'])->middleware('auth:api');
    Route::post('/ticketsOrder/sendComment', [Comment::class, 'addComment'])->middleware('auth:api');

    Route::post('/ticketsOrder/toChanceStatus/{id}',
        [OrderTickets::class, 'toChanceStatus'])->middleware('auth:api')->middleware('admin');

    Route::get('/ticketsOrder/getTicketPdf/{id}',[
        OrderTickets::class, 'getUrlListForPdf'
    ])->middleware('auth:api');
});


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('isCorrectRole', 'isCorrectRole');
});
