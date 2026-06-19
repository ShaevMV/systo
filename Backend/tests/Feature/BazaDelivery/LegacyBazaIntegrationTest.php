<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommand;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsCommandHandler;
use Tickets\Ticket\CreateTickets\Application\PushTicketLive\PushTicketsLiveCommand;
use Tickets\Ticket\CreateTickets\Application\PushTicketLive\PushTicketsLiveCommandHandler;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Интеграция классического (legacy) флоу с трекингом доставки в Baza: вместо синхронного
 * setInBaza/setInBazaLive + DomainException — постановка трекаемой доставки через диспетчер.
 * Главное: сбой Baza больше НЕ роняет смену статуса (хендлер не пишет в Baza сам, а ставит задачу).
 */
class LegacyBazaIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(Uuid $id, ?Uuid $typeTicketId, ?Uuid $curatorId = null): TicketResponse
    {
        return new TicketResponse(
            name: 'Тест Гость',
            kilter: 100,
            uuid: $id,
            status: 'paid',
            email: 'guest@example.com',
            phone: '+70000000000',
            city: 'Москва',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            type_ticket_id: $typeTicketId,
            curator_id: $curatorId,
        );
    }

    public function test_push_handler_dispatches_tracked_el_delivery_without_throwing(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticket($ticketId, Uuid::random()));
        // Хендлер больше НЕ пишет в Baza сам — только ставит трекаемую доставку.
        $tickets->expects($this->never())->method('setInBaza');

        $handler = new PushTicketsCommandHandler($tickets, app(BazaDeliveryDispatcher::class));
        $handler(new PushTicketsCommand($ticketId));

        $this->assertDatabaseHas('baza_deliveries', [
            'ticket_id' => $ticketId->value(),
            'target' => 'el_tickets',
            'status' => 'queued',
        ]);
        Queue::assertPushed(DeliverTicketToBazaJob::class, 1);
    }

    public function test_push_handler_routes_list_ticket_to_spisok(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->method('getTicket')->willReturn($this->ticket($ticketId, null, Uuid::random()));

        $handler = new PushTicketsCommandHandler($tickets, app(BazaDeliveryDispatcher::class));
        $handler(new PushTicketsCommand($ticketId));

        $this->assertDatabaseHas('baza_deliveries', [
            'ticket_id' => $ticketId->value(),
            'target' => 'spisok_tickets',
        ]);
    }

    public function test_push_handler_skips_when_nothing_to_write(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        // Ни куратора, ни type_ticket_id → нечего писать в Baza.
        $tickets->method('getTicket')->willReturn($this->ticket($ticketId, null, null));

        $handler = new PushTicketsCommandHandler($tickets, app(BazaDeliveryDispatcher::class));
        $handler(new PushTicketsCommand($ticketId));

        $this->assertDatabaseMissing('baza_deliveries', ['ticket_id' => $ticketId->value()]);
        Queue::assertNotPushed(DeliverTicketToBazaJob::class);
    }

    public function test_live_handler_dispatches_tracked_live_delivery(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        $tickets = $this->createMock(TicketsRepositoryInterface::class);
        $tickets->expects($this->never())->method('setInBazaLive');

        $handler = new PushTicketsLiveCommandHandler($tickets, app(BazaDeliveryDispatcher::class));
        $handler(new PushTicketsLiveCommand(777, $ticketId));

        $this->assertDatabaseHas('baza_deliveries', [
            'ticket_id' => $ticketId->value(),
            'target' => 'live_tickets',
            'number' => 777,
            'status' => 'queued',
        ]);
        Queue::assertPushed(DeliverTicketToBazaJob::class, 1);
    }
}
