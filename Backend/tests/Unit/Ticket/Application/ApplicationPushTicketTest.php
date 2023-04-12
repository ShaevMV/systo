<?php

namespace Tests\Unit\Ticket\Application;

use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicket;

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
        $result = $this->pushTicket->pushTicket(new Uuid('003ff47c-4330-435e-82a9-1fec77c2a8a0'));
        self::assertTrue(in_array('003ff47c-4330-435e-82a9-1fec77c2a8a0', $result));
    }
}
