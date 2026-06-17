<?php

declare(strict_types=1);

use App\Http\Controllers\EmailDelivery\EmailDeliveryController;
use Illuminate\Support\Facades\Route;

// Контроль доставки писем (Ф2 системы писем): список/деталь/повтор. admin-only (содержит ПДн).
// Отправку выполняет MailDispatcher/SendEmailJob; здесь — только просмотр и повторная отправка.
Route::prefix('v1/emailDelivery')->middleware(['auth:api', 'admin'])->group(static function (): void {
    Route::post('/getList', [EmailDeliveryController::class, 'getList']);
    Route::get('/getItem/{id}', [EmailDeliveryController::class, 'getItem']);
    Route::post('/resend/{id}', [EmailDeliveryController::class, 'resend']);
});
