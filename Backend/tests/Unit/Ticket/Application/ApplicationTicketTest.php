<?php

namespace Tests\Unit\Ticket\Application;

use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\TicketApplication;

class ApplicationTicketTest extends TestCase
{
    use DatabaseTransactions;

    private TicketApplication $TicketApplication;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var TicketApplication $createTicketApplication */
        $createTicketApplication = $this->app->get(TicketApplication::class);
        $this->TicketApplication = $createTicketApplication;
    }

    /**
     * @throws Throwable
     */
    public function test_in_create_pdf(): void
    {
        $tickets = $this->TicketApplication->createList(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            [
                new GuestsDto(
                    'test',
                    new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)
                ),
            ]
        );
        self::assertNotEmpty($tickets);
        self::assertCount(1, $tickets);
    }

    public function test_in_get_list_pdf(): void
    {
        $tickets = $this->TicketApplication->getPdfList(
            new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)
        );

        self::assertEquals(
            'http://localhost/storage/tickets/56f04400-02ab-4cbe-bfd4-4f7dda23d675.pdf',
            $tickets->getUrls()[0]
        );
    }
}
