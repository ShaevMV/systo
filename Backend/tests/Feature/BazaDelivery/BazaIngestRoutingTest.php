<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
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
 * Ф3 (PR-b): доставка билета в Baza маршрутизируется через ingest-API с fallback на прямую запись.
 *
 * Канал включается конфигом services.baza_ingest (url+token). Успех API → прямую запись пропускаем;
 * HTTP-ошибка / success:false / выключенный канал → откат на текущую прямую запись (поведение цело).
 */
class BazaIngestRoutingTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://baza.test';

    private function enableChannel(): void
    {
        config([
            'services.baza_ingest.url' => self::BASE,
            'services.baza_ingest.token' => 'ingest-token',
        ]);
    }

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

    public function test_ingest_success_skips_direct_write(): void
    {
        $this->enableChannel();
        Http::fake([self::BASE.'/*' => Http::response(['success' => true], 200)]);

        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->expects($this->never())->method('setInBaza'); // ingest применил → прямую запись не зовём

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
        Http::assertSent(fn ($req) => $req->url() === self::BASE.'/api/baza/ingest/ticket'
            && $req['target'] === 'el_tickets'
            && $req->hasHeader('X-Baza-Token', 'ingest-token'));
    }

    public function test_http_error_falls_back_to_direct_write(): void
    {
        $this->enableChannel();
        Http::fake([self::BASE.'/*' => Http::response('boom', 500)]);

        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->expects($this->once())->method('setInBaza')->willReturn(true); // fallback

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_success_false_falls_back_to_direct_for_live(): void
    {
        $this->enableChannel();
        // Baza явно не применила (нет live-номера) → откат на прямую запись.
        Http::fake([self::BASE.'/*' => Http::response(['success' => false], 200)]);

        $id = $this->queuedDelivery(BazaDeliveryDispatcher::TARGET_LIVE, number: 555);

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->once())->method('setInBazaLive')->with(555)->willReturn(true);

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
    }

    public function test_channel_disabled_uses_direct_write_only(): void
    {
        // Канал не настроен (дефолт) → ingest не дёргаем, только прямая запись.
        config(['services.baza_ingest.url' => null, 'services.baza_ingest.token' => null]);
        Http::fake();

        $id = $this->queuedDelivery();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticketResponse());
        $tickets->expects($this->once())->method('setInBaza')->willReturn(true);

        $this->runJob($id, $tickets);

        $this->assertSame(BazaDeliveryStatus::DELIVERED, app(BazaDeliveryRepositoryInterface::class)->findById($id)->getStatus());
        Http::assertNothingSent();
    }
}
