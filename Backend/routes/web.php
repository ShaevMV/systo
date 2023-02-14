<?php

use Illuminate\Support\Facades\Route;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

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

Route::get('/tikets', function () {

    $qrCode = App::make(\Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService::class)->createQrCode(new \Tickets\Shared\Domain\ValueObject\Uuid('0c5775e0-357a-4d44-8626-ce0f838ed422'));

    return view('pdf', [
        'url' => $qrCode->getDataUri(),
        'name' => 'Митрофан Шаев',
        'email' => 'test@test.ru',
        'kilter' => 1000
    ]);
});
