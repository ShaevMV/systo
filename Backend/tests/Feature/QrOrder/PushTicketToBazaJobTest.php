<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Carbon\Carbon;
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\QrOrder\Application\Job\PushTicketToBazaJob;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Изолированная задача записи билета в Baza:
 *  - setInBaza вернул false (шина Baza недоступна) → задача бросает исключение → очередь ретраит;
 *  - setInBaza вернул true → успех, без исключения (setInBaza идемпотентен, дублей нет).
 */
class PushTicketToBazaJobTest extends TestCase
{
    private function response(): TicketResponse
    {
        return new TicketResponse(
            name: 'Тест Гость',
            kilter: 20001,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'test@example.com',
            phone: '+70000000000',
            city: 'Москва',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            type_ticket_id: Uuid::random(),
        );
    }

    /** Билет заказа-списка: задан curator_id → TicketResponse::isList() = true. */
    private function listResponse(): TicketResponse
    {
        return new TicketResponse(
            name: 'Списочник',
            kilter: 20002,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'list@example.com',
            phone: '+70000000000',
            city: 'Москва',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            curator_id: Uuid::random(),
            curator_email: 'curator@example.com',
            curator_name: 'Иван Куратор',
            project: 'Смена 1',
        );
    }

    public function test_throws_on_baza_failure_to_trigger_retry(): void
    {
        $repository = $this->createMock(TicketsRepositoryInterface::class);
        $repository->method('setInBaza')->willReturn(false);

        $this->expectException(RuntimeException::class);

        (new PushTicketToBazaJob($this->response()))->handle($repository);
    }

    public function test_succeeds_when_baza_write_ok(): void
    {
        $repository = $this->createMock(TicketsRepositoryInterface::class);
        $repository->expects($this->once())->method('setInBaza')->willReturn(true);

        (new PushTicketToBazaJob($this->response()))->handle($repository);

        // Дошли сюда без исключения — запись прошла.
        $this->assertTrue(true);
    }

    public function test_routes_list_ticket_to_spisok(): void
    {
        // Списочный билет (isList) → spisok_tickets, а не el_tickets.
        $repository = $this->createMock(TicketsRepositoryInterface::class);
        $repository->expects($this->once())->method('setInBazaList')->willReturn(true);
        $repository->expects($this->never())->method('setInBaza');

        (new PushTicketToBazaJob($this->listResponse()))->handle($repository);

        $this->assertTrue(true);
    }
}
