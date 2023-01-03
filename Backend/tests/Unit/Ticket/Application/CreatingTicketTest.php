<?php

namespace Tests\Unit\Ticket\Application;

use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\Create\CreateTicketApplication;

class CreatingTicketTest extends TestCase
{
    use DatabaseTransactions;

    private CreateTicketApplication $createTicketApplication;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var CreateTicketApplication $createTicketApplication */
        $createTicketApplication = $this->app->get(CreateTicketApplication::class);
        $this->createTicketApplication = $createTicketApplication;
    }

    /**
     * @throws \Throwable
     */
    public function test_in_create_pdf():void
    {
        $tickets = $this->createTicketApplication->createList(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            [
                'test'
            ]
        );
        self::assertNotEmpty($tickets);
        self::assertCount(1,$tickets);
    }
}
