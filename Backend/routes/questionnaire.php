<?php

declare(strict_types=1);

use App\Http\Controllers\Questionnaire\QuestionnaireController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/questionnaire')->group(static function (): void {
    Route::post('/load', [QuestionnaireController::class, 'loadQuestionnaireList']);
    Route::post('/send/{orderId}/{ticketId}', [QuestionnaireController::class, 'setQuestionnaire']);
    Route::post('/notification/{id}/{ticketId}', [QuestionnaireController::class, 'setQuestionnaire']);
    Route::get('/get/{id}', [QuestionnaireController::class, 'getQuestionnaire']);
})->middleware('auth:api')
    ->middleware('admin');
