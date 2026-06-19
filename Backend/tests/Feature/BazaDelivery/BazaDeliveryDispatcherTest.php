<?php

declare(strict_types=1);

namespace Tests\Feature\BazaDelivery;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\BazaDelivery\Application\BazaDeliveryContext;
use Tickets\BazaDelivery\Application\BazaDeliveryDispatcher;
use Tickets\BazaDelivery\Application\Job\DeliverTicketToBazaJob;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

/**
 * Постановка доставки билета в Baza в очередь с трекингом: создание записи queued, запись истории
 * baza_queued, диспатч задачи, идемпотентность по (ticket_id, target).
 */
class BazaDeliveryDispatcherTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(?Uuid $id = null, bool $list = false): TicketResponse
    {
        return new TicketResponse(
            name: 'Тест Гость',
            kilter: 12345,
            uuid: $id ?? Uuid::random(),
            status: 'paid',
            email: 'guest@example.com',
            phone: '+70000000000',
            city: 'Москва',
            comment: null,
            date_order: Carbon::now(),
            festival_id: Uuid::random(),
            type_ticket_id: $list ? null : Uuid::random(),
            order_id: Uuid::random(),
            curator_id: $list ? Uuid::random() : null,
        );
    }

    public function test_dispatch_creates_queued_record_history_and_job(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        $id = app(BazaDeliveryDispatcher::class)->dispatch(
            $this->ticket($ticketId),
            new BazaDeliveryContext(source: 'org_event', actorType: ActorType::SYSTEM),
        );

        $row = app(BazaDeliveryRepositoryInterface::class)->findByTicketTarget($ticketId, BazaDeliveryDispatcher::TARGET_EL);
        $this->assertNotNull($row);
        $this->assertTrue($row->getId()->equals($id));
        $this->assertSame(BazaDeliveryStatus::QUEUED, $row->getStatus());
        $this->assertSame(BazaDeliveryDispatcher::TARGET_EL, $row->getTarget());
        $this->assertSame(0, $row->getAttempts());

        $history = app(HistoryRepositoryInterface::class)->getByAggregateId($id->value());
        $this->assertNotEmpty($history);
        $this->assertSame('baza_queued', $history[0]->eventName);

        Queue::assertPushed(DeliverTicketToBazaJob::class);
    }

    public function test_dispatch_routes_list_ticket_to_spisok(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();

        app(BazaDeliveryDispatcher::class)->dispatch(
            $this->ticket($ticketId, list: true),
            new BazaDeliveryContext(source: 'org_event'),
        );

        $repo = app(BazaDeliveryRepositoryInterface::class);
        $this->assertNotNull($repo->findByTicketTarget($ticketId, BazaDeliveryDispatcher::TARGET_SPISOK));
        $this->assertNull($repo->findByTicketTarget($ticketId, BazaDeliveryDispatcher::TARGET_EL));
    }

    public function test_dispatch_is_idempotent_when_already_delivered(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();
        $dispatcher = app(BazaDeliveryDispatcher::class);
        $repo = app(BazaDeliveryRepositoryInterface::class);

        $id = $dispatcher->dispatch($this->ticket($ticketId), new BazaDeliveryContext(source: 'org_event'));
        $repo->markDelivered($id);

        // Повторный диспатч уже доставленного — не пере-доставляем, тот же id, новой задачи нет.
        $again = $dispatcher->dispatch($this->ticket($ticketId), new BazaDeliveryContext(source: 'org_event'));

        $this->assertTrue($again->equals($id));
        $this->assertSame(BazaDeliveryStatus::DELIVERED, $repo->findById($id)->getStatus());
        Queue::assertPushed(DeliverTicketToBazaJob::class, 1);
    }

    public function test_dispatch_requeues_stuck_failed_record(): void
    {
        Queue::fake();
        $ticketId = Uuid::random();
        $dispatcher = app(BazaDeliveryDispatcher::class);
        $repo = app(BazaDeliveryRepositoryInterface::class);

        $id = $dispatcher->dispatch($this->ticket($ticketId), new BazaDeliveryContext(source: 'org_event'));
        $repo->markFailed($id, 'Baza недоступна');

        // Повторный диспатч застрявшего failed — та же строка, статус → queued, новая задача.
        $again = $dispatcher->dispatch($this->ticket($ticketId), new BazaDeliveryContext(source: 'org_event'));

        $this->assertTrue($again->equals($id));
        $this->assertSame(BazaDeliveryStatus::QUEUED, $repo->findById($id)->getStatus());
        Queue::assertPushed(DeliverTicketToBazaJob::class, 2);
    }
}
