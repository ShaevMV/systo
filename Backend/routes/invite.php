<?php

declare(strict_types=1);

use App\Http\Controllers\Invite\InviteController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/invite')->group(static function (): void {
    // создать получить ссылку
    Route::get('/getInviteLink', [InviteController::class, 'getInviteLink'])->middleware('auth:api');
    Route::get('/isCorrectInviteLink/{userId}', [InviteController::class, 'isCorrectInviteLink']);
});

