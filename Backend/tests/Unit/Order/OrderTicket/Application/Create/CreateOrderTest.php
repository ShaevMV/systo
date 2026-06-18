<?php
declare(strict_types=1);

namespace Tests\Unit\Order\OrderTicket\Application\Create;

use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\Create\CreateOrder;
use Tickets\Order\OrderTicket\Domain\OrderTicket;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Order\OrderTicket\Service\TicketService;

class CreateOrderTest extends TestCase
{

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
     * Критично: id заказа можно задать с клиента — внешняя система (qr) и org
     * должны иметь ОДИНАКОВЫЙ id заказа. fromState использует переданный id,
     * и он сохраняется в БД (а не генерируется новый).
     *
     * @throws Throwable
     */
    public function test_provided_order_id_is_used_and_persisted(): void
    {
        $id = '11111111-2222-4333-8444-555555555555';

        $request = [
            'id' => $id,
            'festival_id' => FestivalHelper::UUID_FESTIVAL,
            'email' => 'admin@admin.ru',
            'phone' => '+9555555555',
            'city' => 'SPB',
            'guests' => [
                [
                    'id' => (string) Uuid::random(),
                    'value' => '321',
                    'email' => null,
                    'number' => null,
                    'festival_id' => FestivalHelper::UUID_FESTIVAL,
                    'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
                    'options' => [],
                    'promo_code' => null,
                    'price_snapshot' => ['base_price' => 1000, 'options_sum' => 0, 'discount' => 0],
                    'is_live_ticket' => false,
                ],
            ],
            'id_buy' => '321',
            'date' => '2023-02-10T17:02',
            'types_of_payment_id' => '3fcded69-4aef-4c4a-a041-52c91e5afd63',
        ];

        $dto = OrderTicketDto::fromState($request, new Uuid(UserSeeder::ID_FOR_USER_UUID));

        // 1) fromState использует переданный id, а не генерирует новый.
        self::assertSame($id, $dto->getId()->value());

        // 2) id сохраняется в БД — внешний id заказа == наш id.
        self::assertTrue($this->createOrder->createAndSave($dto));
        $this->assertDatabaseHas('order_tickets', ['id' => $id]);
    }

    /**
     * @throws JsonException
     */
    public function dataProvider(): array
    {
        // v2.6.0: убран multi-fest билет — заказ создаётся с обычным «Оргвзнос»
        // (ID_FOR_FIRST_WAVE), оба гостя на одном фестивале. Каждый гость — это
        // OrderGuestLine с собственным ticket_type_id и снимком цены (price_snapshot).
        $guest = static fn (string $id, string $value): array => [
            'id' => $id,
            'value' => $value,
            'email' => null,
            'number' => null,
            'festival_id' => FestivalHelper::UUID_FESTIVAL,
            'ticket_type_id' => TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
            'options' => [],
            'promo_code' => null,
            'price_snapshot' => [
                'base_price' => 1000,
                'options_sum' => 0,
                'discount' => 0,
            ],
            'is_live_ticket' => false,
        ];

        $request = [
            'festival_id' => FestivalHelper::UUID_FESTIVAL,
            'email' => 'admin@admin.ru',
            'phone' => '+9555555555',
            'city' => 'SPB',
            'guests' => [
                $guest((string) Uuid::random(), '321'),
                $guest((string) Uuid::random(), '321321'),
            ],
            'id_buy' => '321',
            'date' => '2023-02-10T17:02',
            'types_of_payment_id' => '3fcded69-4aef-4c4a-a041-52c91e5afd63',
        ];

        $orderTicketDto = OrderTicketDto::fromState(
            $request,
            new Uuid(UserSeeder::ID_FOR_USER_UUID),
        );

        return [
            [$orderTicketDto]
        ];
    }
}

