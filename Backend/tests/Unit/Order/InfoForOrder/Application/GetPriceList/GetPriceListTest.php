<?php

namespace Tests\Unit\Order\InfoForOrder\Application\GetPriceList;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\InfoForOrder\Application\GetInfoForOrder\GetInfoForOrder;
use Tickets\Order\InfoForOrder\Application\GetPriceList\GetPriceList;
use Tickets\Order\InfoForOrder\Response\TicketTypeDto;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;

class GetPriceListTest extends TestCase
{
    private GetPriceList $getPriceList;
    private GetInfoForOrder $allInfoForOrderingTicketsSearcher;

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

        /** @var GetInfoForOrder $allInfoForOrderingTicketsSearcher */
        $allInfoForOrderingTicketsSearcher = $this->app->get(GetInfoForOrder::class);
        $this->allInfoForOrderingTicketsSearcher = $allInfoForOrderingTicketsSearcher;
    }

    public function test_in_correct_get_list(): void
    {
        $r = $this->allInfoForOrderingTicketsSearcher->getAllPrice(new Uuid(FestivalHelper::UUID_FESTIVAL));
        self::assertCount(6, $r->getTicketType());
    }


    public function test_in_correct_get_price():void
    {
        $r = $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(new Uuid(FestivalHelper::UUID_SECOND_FESTIVAL))
            ->getListTicketTypeDto();

        self::assertCount(2, $r[0]->getFestivalIdList());
    }
}
