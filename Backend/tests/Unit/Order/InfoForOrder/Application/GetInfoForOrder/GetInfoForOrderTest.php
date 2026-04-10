<?php

namespace Tests\Unit\Order\InfoForOrder\Application\GetInfoForOrder;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Festival\Application\GetInfoForOrder\GetInfoForOrder;
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
        self::assertGreaterThan(0, count($r->getTicketType()));
    }

    public function test_in_correct_get_price(): void
    {
        $r = $this->allInfoForOrderingTicketsSearcher
            ->getInfoForOrderingDto(new Uuid(FestivalHelper::UUID_FESTIVAL))
            ->getListTicketTypeDto();

        self::assertGreaterThan(0, $r->getTicketType()[0]->getPrice());
    }
}
