<?php


declare(strict_types=1);

use App\Http\Controllers\Festival\FestivalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/account')->group(static function (): void {
    // получить список всех пользователей
    Route::get('/getList',[\App\Http\Controllers\Account\AccountController::class, 'getList']);
        /*->middleware('auth:api')
        ->middleware('admin');*/

});
