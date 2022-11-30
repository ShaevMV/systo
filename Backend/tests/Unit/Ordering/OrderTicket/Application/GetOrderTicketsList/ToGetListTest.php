<?php

namespace Tests\Unit\Ordering\OrderTicket\Application\GetOrderTicketsList;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ordering\OrderTicket\Application\GetOrderTicketsList\ToGetList;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ToGetListTest extends TestCase
{
    private ToGetList $toGetList;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var ToGetList $toGetList */
        $toGetList = $this->app->get(ToGetList::class);

        $this->toGetList = $toGetList;
    }

    public function test_is_correct_list(): void
    {
        $result = $this->toGetList->byUser(new Uuid('b9df62af-252a-4890-afd7-73c2a356c259'));
        $a = $result->toArray();
        self::assertNotEmpty($result);
    }
}
