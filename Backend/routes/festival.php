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
});
