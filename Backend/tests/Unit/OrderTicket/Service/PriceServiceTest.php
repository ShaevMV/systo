<?php

namespace Tests\Unit\OrderTicket\Service;

use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypeTicketsPriceSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Service\PriceService;
use Tickets\Shared\Domain\ValueObject\Uuid;

class PriceServiceTest extends TestCase
{
    use DatabaseTransactions;

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
            PromoCodSeeder::NAME_FOR_SYSTO,
        );
        self::assertEquals(TypeTicketsPriceSeeder::PRICE_FOR_SECOND_WAVE*2, $result->getPrice());
        self::assertNotEquals(TypeTicketsSeeder::DEFAULT_PRICE, $result->getPrice());

        self::assertEquals(
            (TypeTicketsPriceSeeder::PRICE_FOR_SECOND_WAVE - PromoCodSeeder::DISCOUNT_FOR_SYSTO) * 2,
            $result->getTotalPrice()
        );
        self::assertEquals(PromoCodSeeder::DISCOUNT_FOR_SYSTO * 2, $result->getDiscount());
    }
}
