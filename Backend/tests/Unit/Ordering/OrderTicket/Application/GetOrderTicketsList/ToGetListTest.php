<?php

namespace Tests\Unit\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypesOfPaymentSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Database\Seeders\UserSeeder;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForAdmin\OrderFilterQuery;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\GetOrder;
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
        $result = $this->toGetList->listByUser(new Uuid('b9df62af-252a-4890-afd7-73c2a356c259'));
        self::assertNotEmpty($result);
    }

    public function test_is_correct_find(): void
    {
        $result = $this->toGetList->getItemById(new Uuid('aa0f70cd-f1ae-4d23-b18d-bd2dca659d12'));
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
