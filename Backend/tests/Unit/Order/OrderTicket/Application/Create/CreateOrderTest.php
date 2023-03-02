<?php
declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Create;

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
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
     * @throws Throwable
     */
    public function test_it_create(OrderTicketDto $orderTicketDto): void
    {
        $orderTicket = OrderTicket::create(
            $orderTicketDto
        );

        $events = $orderTicket->pullDomainEvents();
        self::assertCount(1, $events);
    }

    /**
     * @dataProvider dataProvider
     * @throws Throwable
     * @throws JsonException
     */
    public function test_it_save_order(OrderTicketDto $orderTicketDto): void
    {
        self::assertTrue($this->createOrder->createAndSave($orderTicketDto));

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
        $request = Json::decode(
            '{
            "festival_id":"9d679bcf-b438-4ddb-ac04-023fa9bff4b2",
            "email":"admin@admin.ru",
            "phone": "+9555555555",
            "city": "SPB",
            "ticket_type_id":"222abc0c-fc8e-4a1d-a4b0-d345cafacf95",
            "guests":[{"value":"321"},{"value":"321321"}],
            "promo_code":"Systo",
            "id_buy":"321",
            "date":"2023-02-10T17:02",
            "types_of_payment_id":"3fcded69-4aef-4c4a-a041-52c91e5afd63"
            }
        ', 1);
        $orderTicketDto = OrderTicketDto::fromState(
            $request,
            new Uuid(UserSeeder::ID_FOR_USER_UUID),
            new PriceDto(1000,
                count($request['guests']) * 100
            ),
        );

        return [
            [$orderTicketDto]
        ];
    }
}

