<?php

declare(strict_types=1);

namespace Tests\Feature\QrOrder;

use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\QrOrder\Application\Job\LinkLiveTicketJob;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Связка живого билета с live_tickets: задача вызывает setInBazaLive(номер, el_ticket_id).
 */
class LinkLiveTicketJobTest extends TestCase
{
    public function test_calls_set_in_baza_live_with_number_and_ticket(): void
    {
        $ticketId = Uuid::random()->value();

        $repository = $this->createMock(TicketsRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('setInBazaLive')
            ->with(777, $this->callback(static fn ($id) => $id instanceof Uuid && $id->value() === $ticketId))
            ->willReturn(true);

        (new LinkLiveTicketJob($ticketId, 777))->handle($repository);

        $this->assertTrue(true);
    }
}
