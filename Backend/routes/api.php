<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('resetPassword', 'resetPassword');
    Route::post('isCorrectRole', 'isCorrectRole');
    Route::post('editProfile', 'editProfile');
    Route::post('editPassword', 'editPassword');
    Route::get('findUserByEmail/:email', 'findUserByEmail');
});
