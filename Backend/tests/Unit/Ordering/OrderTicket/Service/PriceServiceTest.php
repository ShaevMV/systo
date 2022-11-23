<?php

namespace Tests\Unit\Ordering\OrderTicket\Service;

use Mockery\ExpectationInterface;
use Mockery\MockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Ordering\InfoForOrder\Repositories\InMemoryMySqlPromoCode;
use Tickets\Ordering\InfoForOrder\Repositories\InMemoryMySqlTicketType;
use Tickets\Ordering\InfoForOrder\Response\PromoCodeDto;
use Tickets\Ordering\InfoForOrder\Response\TicketTypeDto;
use Tickets\Ordering\OrderTicket\Service\PriceService;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PriceServiceTest extends TestCase
{
    private PriceService $priceService;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();


        $this->mock(InMemoryMySqlTicketType::class, static function (MockInterface $mock) {
            /** @var ExpectationInterface $method */
            $method = $mock->shouldReceive('getById');

            $method->andReturn(TicketTypeDto::fromState([
                'id' => Uuid::random()->value(),
                'name' => 'test',
                'price' => 1000,
                'groupLimit' => null,
            ]));
        });

        $this->mock(InMemoryMySqlPromoCode::class, static function (MockInterface $mock) {
            /** @var ExpectationInterface $method */
            $method = $mock->shouldReceive('find');

            $method->andReturn(PromoCodeDto::fromState([
                'id' => Uuid::random()->value(),
                'name' => 'test',
                'discount' => 100.0
            ]));
        });

        $priceService = $this->app->get(PriceService::class);
        /** @var PriceService $priceService */
        $this->priceService = $priceService;
    }

    public function test_in_correct_get_priceDto(): void
    {
        $result = $this->priceService->getPriceDto(Uuid::random(), 2, 'Systo');
        self::assertEquals(2000, $result->getTotalPrice());
        self::assertEquals(100.0, $result->getDiscount());
    }
}
