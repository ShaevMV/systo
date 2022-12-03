<?php

namespace Tests\Unit\Ordering\OrderTicket\Application\Create;

use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Ordering\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Shared\Domain\ValueObject\Status;

class CreateOrderTest extends TestCase
{
    private CreateOrder $createOrder;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(InMemoryMySqlOrderTicketRepository::class, static function (MockInterface $mock) {
            /** @var ExpectationInterface $method */
            $method = $mock->shouldReceive('create');

            $method->andReturn(true);
        });

        /** @var CreateOrder $createOrder */
        $createOrder = $this->app->get(CreateOrder::class);
        $this->createOrder = $createOrder;
    }


    /**
     * @throws \Throwable
     * @throws JsonException
     */
    public function test_it_create_order(): void
    {
        $orderTicketDto = OrderTicketDto::fromState([
            'user_id' => '3e1b8039-fc6b-4582-ba02-02208f2ef770',
            'date' => "2022-11-17T22:38",
            'email' => 'jadiyasss@gmail.com',
            'ticket_type_id'=> 'd329da25-2e51-4301-adfe-54b982fcdef3',
            'guests' => Json::decode('[{"value":"321"},{"value":"321321"}]'),
            'promo_code' => 'Systo',
            'types_of_payment_id' => "02c5a0cb-e94a-44bb-b1e7-fbc3e3118f76",
            'price' => 1000,
            'discount' => 100.00,
            'status' => Status::NEW
        ]);

        $this->createOrder->creating($orderTicketDto, 'testTest');

        self::assertTrue(true);
    }
}

