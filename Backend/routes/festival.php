<?php

declare(strict_types=1);

use App\Http\Controllers\Festival\FestivalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/festival')->group(static function (): void {
    // получить информацию о данных для покупки билета
    Route::get('/load',
        [FestivalController::class, 'getInfoForOrder']);

    Route::get('/loadByTicketType/{ticketTypeId}',
        [FestivalController::class, 'loadByTicketType']);

    // получить данные о стоимости
    Route::get('/getListPrice',
        [FestivalController::class, 'getPriceList']);

    // получения списка всех типов билетов
    Route::get('/getTicketTypeList',
        [FestivalController::class, 'getTicketTypeList'])
        ->middleware('auth:api')
        ->middleware('admin');
    // получения списка всех фестивалей
    Route::get('/getFestivalList', [FestivalController::class, 'getFestivalList']);

    // CRUD каталога фестивалей (мастер на org)
    // чтение — публичное (как у location), запись — только admin
    Route::post('/getList', [FestivalController::class, 'getList']);
    Route::get('/getItem/{id}', [FestivalController::class, 'getItem']);

    // создание фестиваля (каталог — мастер на org), только admin
    Route::post('/create', [FestivalController::class, 'create'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/edit/{id}', [FestivalController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::delete('/delete/{id}', [FestivalController::class, 'delete'])
        ->middleware('auth:api')
        ->middleware('admin');

    // журнал изменений фестиваля (domain_history, aggregate_type=festival)
    Route::get('/getHistory/{id}', [FestivalController::class, 'getHistory'])
        ->middleware('auth:api')
        ->middleware('admin');
});
