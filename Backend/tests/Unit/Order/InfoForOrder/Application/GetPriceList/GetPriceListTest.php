<?php

namespace Tests\Unit\Order\InfoForOrder\Application\GetPriceList;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\AllInfoForOrderingTicketsSearcher;
use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceList;

class GetPriceListTest extends TestCase
{
    private GetPriceList $getPriceList;
    private AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var GetPriceList $getPriceList */
        $getPriceList = $this->app->get(GetPriceList::class);
        $this->getPriceList = $getPriceList;

        /** @var AllInfoForOrderingTicketsSearcher $allInfoForOrderingTicketsSearcher */
        $allInfoForOrderingTicketsSearcher = $this->app->get(AllInfoForOrderingTicketsSearcher::class);
        $this->allInfoForOrderingTicketsSearcher = $allInfoForOrderingTicketsSearcher;
    }

    public function test_in_correct_get_list(): void
    {
        $r = $this->getPriceList->getAllPrice();
        self::assertCount(6, $r->getTicketType());
    }


    public function test_in_correct_get_price():void
    {
        $r = $this->allInfoForOrderingTicketsSearcher->getInfo()->toArray();


    }
}
