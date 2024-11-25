<?php
declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Create;

use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Order\OrderTicket\Service\TicketService;

class CreateOrderTest extends TestCase
{
    use DatabaseTransactions;

    private CreateOrder $createOrder;
    private InMemoryMySqlOrderTicketRepository $orderRepository;
    private TicketService $ticketService;

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

        /** @var TicketService $ticketService */
        $ticketService = $this->app->get(TicketService::class);
        $this->ticketService = $ticketService;
    }

    /**
     * @dataProvider dataProvider
     * @throws Throwable
     */
    public function test_it_create(OrderTicketDto $orderTicketDto): void
    {
        $orderTicket = OrderTicket::create(
            $orderTicketDto,
            1
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
        $order = $this->orderRepository->findOrder($orderTicketDto->getId())->toArray();
        $guests = json_decode($order['guests'], true);

        self::assertCount(2, $guests);
    }


    /**
     * @throws JsonException
     */
    public function dataProvider(): array
    {
        $request = Json::decode(
            '{
            "festival_id":"' . FestivalHelper::UUID_FESTIVAL . '",
            "email":"admin@admin.ru",
            "phone": "+9555555555",
            "city": "SPB",
            "ticket_type_id":"' . TypeTicketsSeeder::ID_FOR_MULTI_FESTIVAL . '",
            "guests":[
                {"value":"321", "festival_id": "' . FestivalHelper::UUID_FESTIVAL . '"},
                {"value":"321321", "festival_id": "' . FestivalHelper::UUID_SECOND_FESTIVAL . '"}
                ],
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

