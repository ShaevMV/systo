<?php

namespace Tests\Unit\Order\OrderTicket\Application\ChanceStatus;

use Database\Seeders\OrderSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\Order\OrderTicket\Application\ChanceStatus\ChanceStatus;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class ChanceStatusTest extends TestCase
{
    private ChanceStatus $chanceStatus;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ChanceStatus $chanceStatus */
        $chanceStatus = $this->app->get(ChanceStatus::class);
        $this->chanceStatus = $chanceStatus;
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_buy(): void
    {
        $this->chanceStatus->chance(
            new Uuid('5f78e8aa-b869-4733-ade8-8b5343306cf7'),
            new Status(Status::PAID)
        );

        self::assertTrue(true);
    }



    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_difficulties_arose(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::DIFFICULTIES_AROSE),
            'Что то пошло не так'
        );

        self::assertTrue(true);
    }

    /**
     * @throws Throwable
     */
    public function test_is_correct_chance_status_to_cancel(): void
    {
        $this->chanceStatus->chance(
            new Uuid(OrderSeeder::ID_FOR_FIRST_ORDER),
            new Status(Status::CANCEL)
        );

        self::assertTrue(true);
    }

}
