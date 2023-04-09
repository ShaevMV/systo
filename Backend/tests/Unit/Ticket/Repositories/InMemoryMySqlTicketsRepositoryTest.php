<?php

namespace Tests\Unit\Ticket\Repositories;

use Tests\TestCase;
use Tickets\Ticket\CreateTickets\Repositories\InMemoryMySqlTicketsRepository;

class InMemoryMySqlTicketsRepositoryTest extends TestCase
{

    private InMemoryMySqlTicketsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var InMemoryMySqlTicketsRepository $repository */
        $repository = $this->app->get(InMemoryMySqlTicketsRepository::class);

        $this->repository = $repository;
    }

    public function test_in_correct_get_all_tickets(): void
    {
        $result = $this->repository->getAllTickets();
        $a = 4;
    }
}
