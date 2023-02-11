<?php

namespace Tests\Unit\OrderTicket\Application\ChanceStatus;

use Database\Seeders\OrderSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Repositories\InMemoryMySqlTicketsRepository;

class ChanceStatusTest extends TestCase
{
    use DatabaseTransactions;

    private ChanceStatus $chanceStatus;
    private InMemoryMySqlOrderTicketRepository $repositoryOrder;
    private InMemoryMySqlTicketsRepository $ticketsRepository;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var ChanceStatus $chanceStatus */
        $chanceStatus = $this->app->get(ChanceStatus::class);
        $this->chanceStatus = $chanceStatus;
        /** @var InMemoryMySqlOrderTicketRepository $repositoryOrder */
        $repositoryOrder = $this->app->get(InMemoryMySqlOrderTicketRepository::class);
        $this->repositoryOrder = $repositoryOrder;
        /** @var InMemoryMySqlTicketsRepository $repositoryTicket */
        $repositoryTicket = $this->app->get(InMemoryMySqlTicketsRepository::class);
        $this->ticketsRepository = $repositoryTicket;
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_buy(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::PAID)
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        $idList = $this->ticketsRepository->getListIdByOrderId(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isPaid());

        self::assertCount(1, $idList);
        self::assertTrue($orderDto->getTicket()[0]->getId()->equals($idList[0]));
    }


    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_difficulties_arose(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::DIFFICULTIES_AROSE),
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isdDifficultiesArose());
        self::assertFalse($orderDto->getTicket()[0]->getId()->equals(new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)));
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_cancel(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::CANCEL)
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isCancel());
        self::assertFalse($orderDto->getTicket()[0]->getId()->equals(new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)));
    }

}
