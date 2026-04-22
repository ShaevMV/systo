<?php

namespace Tests\Unit\Ticket\Application;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ticket\CreateTickets\Application\PushTicket;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

class ApplicationPushTicketTest extends TestCase
{
    private PushTicket $pushTicket;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var PushTicket $pushTicket */
        $pushTicket = $this->app->get(PushTicket::class);
        $this->pushTicket = $pushTicket;
    }

    public function test_in_correct_push(): void
    {
        /** @var TicketsRepositoryInterface $repository */
        $repository = $this->app->get(TicketsRepositoryInterface::class);
        $ids = $repository->getAllTicketsId();
        self::assertIsArray($ids);
    }
}
