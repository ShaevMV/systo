<?php

declare(strict_types=1);

use App\Http\Controllers\Questionnaire\QuestionnaireController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/questionnaire')->group(static function (): void {
    Route::post('/load', [QuestionnaireController::class, 'loadQuestionnaireList']);
    Route::post('/send/{orderId}/{ticketId}', [QuestionnaireController::class, 'setQuestionnaire']);
    Route::post('/sendNewUser', [QuestionnaireController::class, 'setNewUserQuestionnaire']);
    Route::post('/notification/{id}', [QuestionnaireController::class, 'replayNotificationUser']);
    Route::post('/approve/{id}', [QuestionnaireController::class, 'approve']);
    Route::get('/get/{id}', [QuestionnaireController::class, 'getQuestionnaire']);
})->middleware('auth:api')
    ->middleware('admin');

// Публичный endpoint для получения questionnaire_type_id
Route::prefix('v1/questionnaire')->group(static function (): void {
    Route::get('/getQuestionnaireType/{orderId}/{ticketId}', [QuestionnaireController::class, 'getQuestionnaireType']);
});
