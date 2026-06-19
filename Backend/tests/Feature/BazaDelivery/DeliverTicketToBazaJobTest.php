<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Throwable;
use Tickets\Auto\Dto\AutoDto;
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Асинхронная запись билета в Baza: успех/сбой/ретрай, маршрутизация по цели, история каждой
 * попытки и жёсткий кап 10 попыток (§6.4).
 */
class DeliverTicketToBazaJobTest extends TestCase
{
    use RefreshDatabase;

    private function ticketResponse(): TicketResponse
    {
        return new TicketResponse(
            name: 'Тест Гость',
            kilter: 12345,
            uuid: Uuid::random(),
            status: 'paid',
            email: 'guest@example.com',
            phone: '+70000000000',
            city: 'Москва',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            type_ticket_id: Uuid::random(),
        );
    }

    /** Создаёт строку доставки в статусе queued и возвращает её id. */
    private function queuedDelivery(string $target = BazaDeliveryDispatcher::TARGET_EL, ?int $number = null): Uuid
    {
        $id = Uuid::random();
        app(BazaDeliveryRepositoryInterface::class)->create(BazaDeliveryDto::queued(
            $id,
            Uuid::random(),
            $target,
            new BazaDeliveryContext(source: 'org_event', number: $number),
        ));

        return $id;
    }

    private function runJob(Uuid $id, TicketsRepositoryInterface $tickets, ?AutoRepositoryInterface $autos = null): void
    {
        (new DeliverTicketToBazaJob($id->value()))->handle(
            app(BazaDeliveryRepositoryInterface::class),
            $tickets,
            app(HistoryRepositoryInterface::class),
            $autos ?? app(AutoRepositoryInterface::class),
        );
    }

    public function test_marks_delivered_when_baza_write_ok(): void
    {
        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->expects($this->once())->method('setInBaza')->willReturn(true);

        $this->runJob($id, $tickets);

        $row = app(BazaDeliveryRepositoryInterface::class)->findById($id);
        $this->assertSame(BazaDeliveryStatus::DELIVERED, $row->getStatus());
        $this->assertSame(1, $row->getAttempts());

        $events = array_map(fn ($h) => $h->eventName, app(HistoryRepositoryInterface::class)->getByAggregateId($id->value()));
        $this->assertContains('baza_sending', $events);
        $this->assertContains('baza_delivered', $events);
    }

    public function test_el_target_uses_stored_subject_blob_not_get_ticket(): void
    {
        // qr-кейс: getTicket не пересоберёт билет (заказ в qr_orders) → берём сохранённый TicketResponse.
        $id = Uuid::random();
        app(BazaDeliveryRepositoryInterface::class)->create(
            BazaDeliveryDto::queued($id, Uuid::random(), BazaDeliveryDispatcher::TARGET_EL, new BazaDeliveryContext(source: 'qr_pipeline')),
            base64_encode(serialize($this->ticketResponse())),
        );

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->never())->method('getTicket');
        $tickets->expects($this->once())->method('setInBaza')->willReturn(true);

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_marks_failed_and_throws_on_baza_failure(): void
    {
        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->method('setInBaza')->willReturn(false);

        $thrown = false;
        try {
            $this->runJob($id, $tickets);
        } catch (RuntimeException) {
            $thrown = true; // бросок → очередь ретраит (attempt 1 < cap)
        }

        $this->assertTrue($thrown);
        $row = app(BazaDeliveryRepositoryInterface::class)->findById($id);
        $this->assertSame(BazaDeliveryStatus::FAILED, $row->getStatus());
        $this->assertSame(1, $row->getAttempts());

        $events = array_map(fn ($h) => $h->eventName, app(HistoryRepositoryInterface::class)->getByAggregateId($id->value()));
        $this->assertContains('baza_failed', $events);
    }

    public function test_routes_list_target_to_spisok(): void
    {
        $id = $this->queuedDelivery(BazaDeliveryDispatcher::TARGET_SPISOK);

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->expects($this->once())->method('setInBazaList')->willReturn(true);
        $tickets->expects($this->never())->method('setInBaza');

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_live_target_uses_set_in_baza_live_with_number(): void
    {
        $id = $this->queuedDelivery(BazaDeliveryDispatcher::TARGET_LIVE, number: 555);

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->never())->method('getTicket');
        $tickets->expects($this->once())->method('setInBazaLive')->with(555)->willReturn(true);

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_cap_of_ten_attempts_then_terminal_failed(): void
    {
        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        // Запись в Baza ровно 10 раз — 11-я и 12-я попытки до Baza уже не доходят (кап).
        $tickets->expects($this->exactly(10))->method('setInBaza')->willReturn(false);

        // 12 «прогонов» очереди (авто-ретрай бросает исключение — ловим, как сделала бы очередь).
        for ($i = 0; $i < 12; $i++) {
            try {
                $this->runJob($id, $tickets);
            } catch (Throwable) {
                // авто-ретрай
            }
        }

        $row = app(BazaDeliveryRepositoryInterface::class)->findById($id);
        $this->assertSame(10, $row->getAttempts(), 'attempts должно остановиться на 10');
        $this->assertSame(BazaDeliveryStatus::FAILED, $row->getStatus(), 'после капа — терминальный failed');
    }

    public function test_auto_target_uses_set_in_baza_auto(): void
    {
        $id = $this->queuedDelivery(BazaDeliveryDispatcher::TARGET_AUTO);
        $ticketId = app(BazaDeliveryRepositoryInterface::class)->findById($id)->getTicketId();

        $autos = $this->createMock(AutoRepositoryInterface::class);
        $autos->method('getById')->willReturn(AutoDto::create($ticketId, 'А123АА777'));
        $autos->expects($this->once())->method('setInBazaAuto')->willReturn(true);

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->never())->method('getTicket');

        $this->runJob($id, $tickets, $autos);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_idempotent_when_already_delivered(): void
    {
        $id = $this->queuedDelivery();
        app(BazaDeliveryRepositoryInterface::class)->markDelivered($id);

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->never())->method('setInBaza');
        $tickets->expects($this->never())->method('getTicket');

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }
}
