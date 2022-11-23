<?php

namespace Tests\Unit\Ordering\OrderTicket\Application\Create;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tests\TestCase;
use Tickets\Ordering\OrderTicket\Application\Create\CreateOrder;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\ValueObject\Status;

class CreateOrderTest extends TestCase
{
    private CreateOrder $createOrder;

    protected function setUp(): void
    {
        parent::setUp();
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
            'discount' => 0.00,
            'status' => Status::NEW
        ]);

        $this->createOrder->creating($orderTicketDto);
    }
}

