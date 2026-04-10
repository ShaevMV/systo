<?php

namespace Tests\Unit\Order\OrderTicket\Application\GetOrderTicketsList;

use Database\Seeders\OrderSeeder;
use Database\Seeders\UserSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin\OrderFilterQuery;
use Tickets\Order\OrderTicket\Application\GetOrderList\GetOrder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Database\Seeders\TypeTicketsSeeder;

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

    public function test_is_filter(): void
    {
        $result = $this->toGetList->listByFilter(
            new OrderFilterQuery(
                new Uuid(FestivalHelper::UUID_FESTIVAL),
                null,
                null,
                null,
                null,
                TypeTicketsSeeder::DEFAULT_PRICE,
                new Uuid(TypeTicketsSeeder::ID_FOR_FIRST_WAVE),
            )
        );

        self::assertNotEmpty($result?->toArray());

        $result = $this->toGetList->listByFilter(
            new OrderFilterQuery(
                Uuid::random(),
            )
        );

        self::assertEmpty($result?->toArray());
    }

    public function test_is_filter_for_multi_festival(): void
    {
        $result = $this->toGetList->listByFilter(
            new OrderFilterQuery(
                new Uuid(FestivalHelper::UUID_FESTIVAL),
                null,
                null,
                null,
                null,
                TypeTicketsSeeder::DEFAULT_MULTI_FESTIVAL_PRICE,
                new Uuid(TypeTicketsSeeder::ID_FOR_MULTI_FESTIVAL),
            )
        );

        self::assertNotEmpty($result?->toArray());
    }
}
