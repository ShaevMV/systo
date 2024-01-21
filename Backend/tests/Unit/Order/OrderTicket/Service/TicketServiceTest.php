<?php

declare(strict_types=1);

namespace Unit\Order\OrderTicket\Service;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\TicketService;

class TicketServiceTest extends TestCase
{
    private TicketService $ticketService;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();
        /** @var TicketService $ticketService */
        $ticketService = $this->app->get(TicketService::class);

        $this->ticketService = $ticketService;
    }

    public function test_insert_init_FestivalId(): void
    {
        $result = $this->ticketService
            ->initFestivalId([
                [
                    'value' => '1235',
                ]
            ], [
                new Uuid(FestivalHelper::UUID_FESTIVAL),
                new Uuid(FestivalHelper::UUID_SECOND_FESTIVAL),
            ]);
        self::assertCount(2, $result);
    }
}
