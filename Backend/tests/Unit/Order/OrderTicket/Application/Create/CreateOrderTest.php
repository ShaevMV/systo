<?php

namespace Tests\Unit\Order\OrderTicket\Application\Create;

use Database\Seeders\TypesOfPaymentSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class CreateOrderTest extends TestCase
{
    use DatabaseTransactions;

    private CreateOrder $createOrder;
    private InMemoryMySqlOrderTicketRepository $orderRepository;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var CreateOrder $createOrder */
        $createOrder = $this->app->get(CreateOrder::class);
        $this->createOrder = $createOrder;

        $orderRepository = $this->app->get(InMemoryMySqlOrderTicketRepository::class);
        /** @var InMemoryMySqlOrderTicketRepository $orderRepository */
        $this->orderRepository = $orderRepository;
    }


    /**
     * @dataProvider dataProvider
     * @throws \Exception
     */
    public function test_it_create(OrderTicketDto $orderTicketDto): void
    {
        $orderTicket = OrderTicket::create($orderTicketDto);
        $events = $orderTicket->pullDomainEvents();
        self::assertCount(1, $events);
    }

    /**
     * @dataProvider dataProvider
     * @throws \Throwable
     * @throws JsonException
     */
    public function test_it_save_order(OrderTicketDto $orderTicketDto): void
    {
        self::assertTrue($this->createOrder->createAndSave($orderTicketDto));
        $findOrder = $this->orderRepository->findOrder($orderTicketDto->getId())->toArray();
        self::assertEquals(
            $this->orderRepository->findOrder($orderTicketDto->getId())->toArray(),
            $orderTicketDto->toArray()
        );
    }


    /**
     * @throws JsonException
     */
    public function dataProvider(): array
    {
        $orderTicketsDto = OrderTicketDto::fromState([
            'id' => Uuid::random()->value(),
            'user_id' => UserSeeder::ID_FOR_USER_UUID,
            'date' => "2022-11-17T22:38",
            'email' => 'jadiyasss@gmail.com',
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'guests' => Json::encode([
                [
                    'value' => 'test'
                ],
                [
                    'value' => 'test'
                ],
            ]),
            'promo_code' => 'Systo',
            'types_of_payment_id' => TypesOfPaymentSeeder::ID_FOR_QIWI,
            'price' => 1000,
            'discount' => 100.00,
            'status' => Status::NEW
        ]);

        return [
            [$orderTicketsDto]
        ];
    }
}

