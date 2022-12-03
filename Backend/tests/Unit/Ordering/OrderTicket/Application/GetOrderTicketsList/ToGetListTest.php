<?php

namespace Tests\Unit\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicket\GetOrder;
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
        $a = $result->toArray();
        self::assertNotEmpty($result);
    }

    public function test_is_correct_find(): void
    {
        $result = $this->toGetList->getItemById(new Uuid('aa0f70cd-f1ae-4d23-b18d-bd2dca659d12'));
        $d = $result->toArray();
        self::assertNotEmpty($result);
    }
}
