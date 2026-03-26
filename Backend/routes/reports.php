<?php

declare(strict_types=1);

use App\Http\Controllers\Reports\ReportExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/reports')->group(static function (): void {
    Route::get('/configs', [ReportExportController::class, 'getConfigs']);
    Route::post('/configs', [ReportExportController::class, 'saveConfig']);
    Route::put('/configs/{id}', [ReportExportController::class, 'updateConfig']);
    Route::delete('/configs/{id}', [ReportExportController::class, 'deleteConfig']);
    Route::post('/export', [ReportExportController::class, 'export']);
});
