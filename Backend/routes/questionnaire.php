<?php

declare(strict_types=1);

use App\Http\Controllers\Questionnaire\QuestionnaireController;
use Illuminate\Support\Facades\Route;

// Админские маршруты
Route::prefix('v1/questionnaire')->group(static function (): void {
    Route::post('/load', [QuestionnaireController::class, 'loadQuestionnaireList']);
    Route::post('/notification/{id}', [QuestionnaireController::class, 'replayNotificationUser']);
    Route::post('/approve/{id}', [QuestionnaireController::class, 'approve']);
    Route::get('/get/{id}', [QuestionnaireController::class, 'getQuestionnaire']);
})->middleware('auth:api')
    ->middleware('admin');

// Публичные маршруты (доступны без аутентификации)
Route::prefix('v1/questionnaire')->group(static function (): void {
    Route::post('/send/{orderId}/{ticketId}', [QuestionnaireController::class, 'setQuestionnaire']);
    Route::post('/sendNewUser', [QuestionnaireController::class, 'setNewUserQuestionnaire']);
    Route::post('/uploadPhoto/{orderId}/{ticketId}', [QuestionnaireController::class, 'uploadPhoto']);
    Route::get('/getQuestionnaireTypeByOrderTicket/{orderId}/{ticketId}', [QuestionnaireController::class, 'getQuestionnaireTypeByOrderTicket']);
    Route::get('/getByOrderTicket/{orderId}/{ticketId}', [QuestionnaireController::class, 'getByOrderTicket']);
});
