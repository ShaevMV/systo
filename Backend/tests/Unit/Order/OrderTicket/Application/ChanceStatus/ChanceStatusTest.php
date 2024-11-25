<?php

namespace Tests\Unit\Order\OrderTicket\Application\ChanceStatus;

use Database\Seeders\FestivalSeeder;
use Database\Seeders\OrderSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
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
            new Status(Status::PAID),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            now:true,
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        $idList = $this->ticketsRepository->getListIdByOrderId(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isPaid());

        self::assertCount(1, $idList);
        self::assertTrue($orderDto->getTicket()[0]->getId()->equals($idList[0]));
        self::assertTrue($orderDto->getTicket()[0]->getFestivalId()->equals(new Uuid(FestivalHelper::UUID_FESTIVAL)));
    }


    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_buy_for_multi_festival(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_MULTI_FESTIVAL_ORDER),
            new Status(Status::PAID),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            now:true,
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_MULTI_FESTIVAL_ORDER));
        $idList = $this->ticketsRepository->getListIdByOrderId(new Uuid(OrderSeeder::ID_FOR_MULTI_FESTIVAL_ORDER));
        self::assertTrue($orderDto->getStatus()->isPaid());

        self::assertCount(2, $idList);
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_difficulties_arose(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::DIFFICULTIES_AROSE),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            'Test',
            true,
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
            new Status(Status::CANCEL),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            'Test',
            true,
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isCancel());
        self::assertFalse($orderDto->getTicket()[0]->getId()->equals(new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)));
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_live_ticket_issued(): void
    {
        $this->test_is_correct_chance_status_to_buy();
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isPaid());
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::LIVE_TICKET_ISSUED),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            now: true
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertTrue($orderDto->getStatus()->isLiveIssued());
        self::assertFalse($orderDto->getTicket()[0]->getId()->equals(new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)));
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_for_live_ticket_to_buy(): void
    {
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_LIVE_FESTIVAL_ORDER));
        self::assertTrue($orderDto->getStatus()->isNewForLive());
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_LIVE_FESTIVAL_ORDER),
            new Status(Status::PAID_FOR_LIVE),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            now:true,
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_LIVE_FESTIVAL_ORDER));
        self::assertTrue($orderDto->getStatus()->isPaidForLive());
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_LIVE_FESTIVAL_ORDER),
            new Status(Status::LIVE_TICKET_ISSUED),
            new Uuid(UserSeeder::ID_FOR_ADMIN_UUID),
            now: true
        );
        $orderDto = $this->repositoryOrder->findOrder(new Uuid(OrderSeeder::ID_FOR_LIVE_FESTIVAL_ORDER));
        self::assertTrue($orderDto->getStatus()->isLiveIssued());
        self::assertFalse($orderDto->getTicket()[0]->getId()->equals(new Uuid(OrderSeeder::ID_FOR_FIRST_TICKET)));
    }

}
