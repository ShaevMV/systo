<?php

namespace Tests\Unit\Order\OrderTicket\Service;

use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Service\PriceService;

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
        $priceService = $this->app->get(PriceService::class);
        /** @var PriceService $priceService */
        $this->priceService = $priceService;
    }

    public function test_in_correct_get_priceDto(): void
    {
        $result = $this->priceService->getPriceDto(
            new Uuid(TypeTicketsSeeder::ID_FOR_FIRST_WAVE),
            2,
            null, // Без промокода
        );

        self::assertGreaterThan(0, $result->getPrice());
        self::assertEquals(0, $result->getDiscount());
        self::assertEquals($result->getPrice(), $result->getTotalPrice());
    }
}
