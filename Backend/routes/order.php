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

    // создать заказ-список (только куратор)
    Route::post('/createList', [OrderTickets::class, 'createList'])
        ->middleware('auth:api')
        ->middleware('role:curator');


    // список всех заказов для АДМИНА
    Route::post('/getList',[OrderTickets::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin');

    Route::post('/getListForFriendly',[OrderTickets::class, 'getFriendlyList'])
        ->middleware('auth:api')
        ->middleware('role:pusher,admin');

    // список заказов-списков для admin/manager
    Route::post('/getListsList', [OrderTickets::class, 'getListsList'])
        ->middleware('auth:api')
        ->middleware('role:admin,manager');

    // список заказов-списков для куратора (свои; admin видит все)
    Route::post('/getCuratorList', [OrderTickets::class, 'getCuratorList'])
        ->middleware('auth:api')
        ->middleware('role:curator,admin');

    // сменить статус заказа (admin/seller/pusher для обычных и live, admin/manager для list-статусов — проверка внутри метода)
    Route::post('/toChangeStatus/{id}', [OrderTickets::class, 'toChangeStatus'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin,pusher,manager');

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
