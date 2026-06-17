<?php

declare(strict_types=1);

use App\Http\Controllers\EmailDelivery\EmailDeliveryController;
use App\Http\Controllers\EmailDelivery\EmailNotificationController;
use Illuminate\Support\Facades\Route;

// Контроль доставки писем (Ф2 системы писем): список/деталь/повтор. admin-only (содержит ПДн).
// Отправку выполняет MailDispatcher/SendEmailJob; здесь — только просмотр и повторная отправка.
Route::prefix('v1/emailDelivery')->middleware(['auth:api', 'admin'])->group(static function (): void {
    Route::post('/getList', [EmailDeliveryController::class, 'getList']);
    Route::get('/getItem/{id}', [EmailDeliveryController::class, 'getItem']);
    Route::post('/resend/{id}', [EmailDeliveryController::class, 'resend']);
});

// Пиксель прочтения письма (Ф3) — публичный (картинка в письме), throttle, токен случайный (≠ id).
Route::get('v1/mail/open/{token}.gif', [EmailDeliveryController::class, 'openPixel'])
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:120,1');

// S2S-приём писем от витрины qr (Ф4): регистрация/пароль и прочие не-заказные письма.
// Канал закрыт сервисным ключом qr (X-QR-Token, middleware qr.ingest), как qrOrder/create.
Route::post('v1/emailNotification/send', [EmailNotificationController::class, 'send'])
    ->middleware('qr.ingest');
