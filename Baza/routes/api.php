<?php

use App\Http\Controllers\Api\IngestTicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Маршруты сканирования и впуска (POST /api/scan, /api/enter) перенесены в routes/web.php
// под middleware('auth'): группа `api` не стартует сессию, поэтому раньше эндпоинты были
// фактически без авторизации, а id сотрудника брался из тела запроса. Теперь они под
// web-группой (сессия + CSRF) и auth — см. routes/web.php.

// S2S-приём билетов от org (Ф3). Заголовок X-Baza-Token (middleware baza.ingest).
// Группа `api` сессию не стартует — это и нужно для машина-машина канала.
Route::post('/baza/ingest/ticket', [IngestTicketController::class, 'ticket'])
    ->middleware('baza.ingest');

// S2S-приём отзыва билета от org (Ф5, PR-6, B6) — закрывает дыру «отмена/возврат».
Route::post('/baza/ingest/revoke', [\App\Http\Controllers\Api\RevokeTicketController::class, 'revoke'])
    ->middleware('baza.ingest');
