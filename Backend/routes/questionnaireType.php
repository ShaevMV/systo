<?php


declare(strict_types=1);

use App\Http\Controllers\QuestionnaireType\QuestionnaireTypeController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1/questionnaireType')->group(static function (): void {
    Route::post('/getList',[QuestionnaireTypeController::class, 'getList']);
    Route::get('/getItem/{id}',[QuestionnaireTypeController::class, 'getItem']);
    Route::get('/getByCode/{code}',[QuestionnaireTypeController::class, 'getByCode']);

    Route::post('/edit/{id}',[QuestionnaireTypeController::class, 'edit']);
    Route::post('/create',[QuestionnaireTypeController::class, 'create']);

    Route::delete('/delete/{id}',[QuestionnaireTypeController::class, 'delete']);
});
