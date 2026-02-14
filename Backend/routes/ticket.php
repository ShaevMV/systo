<?php

declare(strict_types=1);

use App\Http\Controllers\Invite\InviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/ticket')->group(static function (): void {
    Route::get('/live/{cash?}', [\App\Http\Controllers\TicketsOrder\LiveTicket::class, 'getNumber']);
});

