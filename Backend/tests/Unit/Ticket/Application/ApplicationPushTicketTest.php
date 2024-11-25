<?php

namespace Tests\Unit\Ticket\Application;

use Nette\Utils\JsonException;
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

    /**
     * @throws JsonException
     * @throws \Throwable
     */
    public function test_in_correct_push(): void
    {
        /** @var TicketsRepositoryInterface $repository */
        $repository = $this->app->get(TicketsRepositoryInterface::class);
        $ids = $repository->getAllTicketsId();
        foreach ($ids as $id) {
            $this->pushTicket->pushTicket($id);
        }
        self::assertTrue(true);
    }
}
