<?php

namespace Tests\Unit\Order\OrderFriendly\Domain;

use Database\Seeders\UserSeeder;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderFriendly\Application\CreateOrder\CreateOrder;
use Tickets\Order\OrderFriendly\Domain\OrderFriendly;
use Tickets\Order\OrderFriendly\Domain\OrderTicketDto;
use Tickets\Order\Shared\Dto\PriceDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderFriendlyTest extends TestCase
{
    private CreateOrder $createOrder;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var  CreateOrder $createOrder */
        $createOrder = $this->app->get(CreateOrder::class);

        $this->createOrder = $createOrder;
    }

    /**
     * @dataProvider dataProvider
     * @throws Throwable
     */
    public function test_in_create(OrderTicketDto $orderTicketDto)
    {
        $this->createOrder->create($orderTicketDto);

        $result = OrderFriendly::create($orderTicketDto,1000);

        $t = 4;
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
            "guests":[{"value":"321"},{"value":"321321"}]
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
