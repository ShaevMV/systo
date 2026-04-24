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

    Route::post('/createCurator', [OrderTickets::class, 'createCurator'])
        ->middleware('auth:api')
        ->middleware('role:curator,curator_pusher');


    // список всех заказов для АДМИНА
    Route::post('/getList',[OrderTickets::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin');

    Route::post('/getListForFriendly',[OrderTickets::class, 'getFriendlyList'])
        ->middleware('auth:api')
        ->middleware('role:pusher,admin');

    // сменить статус заказа АДМИН
    Route::post('/toChangeStatus/{id}', [OrderTickets::class, 'toChangeStatus'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin,pusher');

    // изменить цену заказа (только admin)
    Route::post('/changePrice/{id}', [OrderTickets::class, 'changePrice'])
        ->middleware('auth:api')
        ->middleware('role:admin');

    // изменить цену заказа (только admin)
    Route::post('/changeTicket/{id}', [OrderTickets::class, 'changeTicket'])
        ->middleware('auth:api')
        ->middleware('role:admin,pusher');

    // Список заказов для Пользователя
    Route::get('/getUserList', [OrderTickets::class, 'getUserList'])
        ->middleware('auth:api');

    // получить определённые заказ
    Route::get('/getItem/{id}', [OrderTickets::class, 'getOrderItem'])
        ->middleware('auth:api');

    Route::get('/getTicketPdf/{id}',[
        OrderTickets::class, 'getUrlListForPdf'
    ])->middleware('auth:api');

    Route::get('/getHistory/{id}', [OrderTickets::class, 'getHistory'])
        ->middleware('auth:api')
        ->middleware('admin');
});


Route::any('/v1/order/succes',[\App\Http\Controllers\Billing\BillingController::class, 'webHook']);
