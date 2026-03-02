<?php

declare(strict_types=1);

use App\Http\Controllers\Questionnaire\QuestionnaireController;
use App\Http\Controllers\TicketsOrder\Comment;
use App\Http\Controllers\TicketsOrder\OrderTickets;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/order')->group(static function (): void {
    // создать заказ
    Route::post('/create',
        [OrderTickets::class, 'create']);

    Route::post('/createFriendly',[OrderTickets::class, 'createFriendly'])
        ->middleware('auth:api')
        ->middleware('role:pusher');


    // список всех заказов для АДМИНА
    Route::post('/getList',[OrderTickets::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin');

    // сменить статус заказа АДМИН
    Route::post('/toChanceStatus/{id}', [OrderTickets::class, 'toChanceStatus'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin');

    // Список заказов для Пользователя
    Route::get('/getUserList', [OrderTickets::class, 'getUserList'])
        ->middleware('auth:api');

    // получить определённые заказ
    Route::get('/getItem/{id}', [OrderTickets::class, 'getOrderItem'])
        ->middleware('auth:api');

    Route::get('/getTicketPdf/{id}',[
        OrderTickets::class, 'getUrlListForPdf'
    ])->middleware('auth:api');
});


Route::any('/v1/order/succes',[\App\Http\Controllers\Billing\BillingController::class, 'webHook']);
