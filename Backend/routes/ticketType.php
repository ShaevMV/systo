<?php


declare(strict_types=1);

use App\Http\Controllers\TicketType\TicketTypeController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/ticketType')->group(static function (): void {
    Route::post('/getList',[TicketTypeController::class, 'getList']);
    Route::get('/getItem/{id}',[TicketTypeController::class, 'getItem']);

    Route::post('/edit/{id}',[TicketTypeController::class, 'edit']);
    Route::post('/create',[TicketTypeController::class, 'create']);

    Route::delete('/delete/{id}',[TicketTypeController::class, 'delete']);
});
