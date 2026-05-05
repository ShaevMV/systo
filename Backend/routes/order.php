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
        ->middleware('role:pusher,pusher_curator');

    // создать заказ-список (куратор и мульти-роль pusher_curator)
    Route::post('/createList', [OrderTickets::class, 'createList'])
        ->middleware('auth:api')
        ->middleware('role:curator,pusher_curator');


    // список всех заказов для АДМИНА
    Route::post('/getList',[OrderTickets::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin');

    Route::post('/getListForFriendly',[OrderTickets::class, 'getFriendlyList'])
        ->middleware('auth:api')
        ->middleware('role:pusher,admin,pusher_curator');

    // список заказов-списков для admin/manager
    Route::post('/getListsList', [OrderTickets::class, 'getListsList'])
        ->middleware('auth:api')
        ->middleware('role:admin,manager');

    // список заказов-списков для куратора (свои; admin видит все)
    Route::post('/getCuratorList', [OrderTickets::class, 'getCuratorList'])
        ->middleware('auth:api')
        ->middleware('role:curator,admin,pusher_curator');

    // сменить статус заказа (admin/seller/pusher для обычных и live, admin/manager для list-статусов — проверка внутри метода)
    Route::post('/toChangeStatus/{id}', [OrderTickets::class, 'toChangeStatus'])
        ->middleware('auth:api')
        ->middleware('role:seller,admin,pusher,manager,pusher_curator');

    // изменить цену заказа (только admin)
    Route::post('/changePrice/{id}', [OrderTickets::class, 'changePrice'])
        ->middleware('auth:api')
        ->middleware('role:admin');

    // изменить цену заказа (только admin)
    Route::post('/changeTicket/{id}', [OrderTickets::class, 'changeTicket'])
        ->middleware('auth:api')
        ->middleware('role:admin,pusher,pusher_curator');

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

    // Авто заказа-списка: добавление и удаление (admin / curator / pusher_curator)
    Route::post('/{id}/auto', [OrderTickets::class, 'addAuto'])
        ->middleware('auth:api')
        ->middleware('role:admin,curator,pusher_curator');

    Route::delete('/{id}/auto/{autoId}', [OrderTickets::class, 'removeAuto'])
        ->middleware('auth:api')
        ->middleware('role:admin,curator,pusher_curator');
});


Route::any('/v1/order/succes',[\App\Http\Controllers\Billing\BillingController::class, 'webHook']);
