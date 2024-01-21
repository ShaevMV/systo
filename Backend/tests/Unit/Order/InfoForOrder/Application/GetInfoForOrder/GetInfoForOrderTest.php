<?php

namespace Tests\Unit\Order\InfoForOrder\Application\GetInfoForOrder;

use Database\Seeders\TypeTicketsPriceSeeder;
use Database\Seeders\TypeTicketsSecondFestivalSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\GetInfoForOrder;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class GetInfoForOrderTest extends TestCase
{
    private GetInfoForOrder $allInfoForOrderingTicketsSearcher;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();


        /** @var GetInfoForOrder $allInfoForOrderingTicketsSearcher */
        $allInfoForOrderingTicketsSearcher = $this->app->get(GetInfoForOrder::class);
        $this->allInfoForOrderingTicketsSearcher = $allInfoForOrderingTicketsSearcher;
    }

    public function test_in_correct_get_list(): void
    {
        $r = $this->allInfoForOrderingTicketsSearcher
            ->getAllPrice(
                new Uuid(FestivalHelper::UUID_FESTIVAL)
            );
        self::assertCount(7, $r->getTicketType());
    }


    public function test_in_correct_get_price():void
    {
        $r = $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(new Uuid(FestivalHelper::UUID_FESTIVAL))
            ->getListTicketTypeDto();

        self::assertEquals(TypeTicketsPriceSeeder::PRICE_FOR_SECOND_WAVE, $r->getTicketType()[0]->getPrice());
    }
}
