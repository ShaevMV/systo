<?php


declare(strict_types=1);

use App\Http\Controllers\Account\AccountController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/account')->group(static function (): void {
    // получить список всех пользователей
    Route::post('/getList',[AccountController::class, 'getList'])
        ->middleware('auth:api')
        ->middleware('admin');
    // получить данные конкретного пользователя
    Route::get('/getItem/{email}',[AccountController::class, 'getItem'])
    ->middleware('auth:api')
        ->middleware('admin');

    // записать данные отдельного пользователя
    Route::post('/edit/{id}',[AccountController::class, 'edit'])
        ->middleware('auth:api')
        ->middleware('admin');

    Route::post('/changeRole/{id}',[AccountController::class, 'changeRole'])
        ->middleware('auth:api')
        ->middleware('admin');
});
