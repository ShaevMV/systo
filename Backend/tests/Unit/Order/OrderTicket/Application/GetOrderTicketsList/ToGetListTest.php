<?php

namespace Tests\Unit\Order\OrderTicket\Application\GetOrderTicketsList;

use Database\Seeders\OrderSeeder;
use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypesOfPaymentSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ToGetListTest extends TestCase
{
    private GetOrder $toGetList;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var GetOrder $toGetList */
        $toGetList = $this->app->get(GetOrder::class);

        $this->toGetList = $toGetList;
    }

    public function test_is_correct_list(): void
    {
        $result = $this->toGetList->listByUser(new Uuid(UserSeeder::ID_FOR_USER_UUID));
        self::assertNotEmpty($result);
    }

    public function test_is_correct_find(): void
    {
        $result = $this->toGetList->getItemById(new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER));
        self::assertNotEmpty($result);
    }

    /**
     * @throws JsonException
     */
    public function test_is_filter(): void
    {
        $result = $this->toGetList->listByFilter(
            new OrderFilterQuery(
                new Uuid(TypeTicketsSeeder::ID_FOR_FIRST_WAVE),
                new Uuid(TypesOfPaymentSeeder::ID_FOR_YANDEX),
                UserSeeder::EMAIL_USER,
                Status::NEW,
                PromoCodSeeder::NAME_FOR_SYSTO
            )
        );

        self::assertNotEmpty($result?->toArray());

        $result = $this->toGetList->listByFilter(
            new OrderFilterQuery(
                new Uuid('222abc1c-fc8e-4a1d-a4b0-d345cafacf55'),
            )
        );

        self::assertEmpty($result?->toArray());
    }
}
