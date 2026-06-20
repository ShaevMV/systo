<?php

declare(strict_types=1);

use App\Http\Controllers\Baza\BazaWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/baza')->group(static function (): void {
    // Приём вебхука «билет прошёл» от Baza (Ф4). S2S-канал: заголовок X-Baza-Token
    // (middleware baza.webhook). Пишет факт входа в domain_history (actor_type=baza),
    // идемпотентно по event_id. Это ВХОДЯЩИЙ канал, отдельный от исходящего qrOrder/* и baza_ingest.
    Route::post('/ticketEntered', [BazaWebhookController::class, 'ticketEntered'])
        ->middleware('baza.webhook');
});
