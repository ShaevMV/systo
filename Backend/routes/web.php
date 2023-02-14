<?php

use Illuminate\Support\Facades\Route;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ticket', function () {

    ini_set('memory_limit', '-1');

    $pdf = (new CreatingQrCodeService())->createPdf(
        new TicketResponse(
            'test',
            1000,
            new Uuid('0c5775e0-357a-4d44-8626-ce0f838ed422'),
            'test@test.ru',
            '+799555545',
            'SPB'
        )

    );
    $pdf->save(storage_path("app/public/tickets/0c5775e0-357a-4d44-8626-ce0f838ed422.pdf"));
    $path = Storage::disk('local')->path("public/tickets/0c5775e0-357a-4d44-8626-ce0f838ed422.pdf");

    return response()->download($path, basename($path));
});
