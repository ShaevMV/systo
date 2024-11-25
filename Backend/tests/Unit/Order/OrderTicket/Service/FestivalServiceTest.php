<?php

declare(strict_types=1);

namespace Unit\Order\OrderTicket\Service;

use Database\Seeders\TypeTicketsSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;

class FestivalServiceTest extends TestCase
{
    private FestivalService $festivalService;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        $festivalService = $this->app->get(FestivalService::class);
        /** @var FestivalService $festivalService */
        $this->festivalService = $festivalService;
    }

    /**
     * @dataProvider dataProvider
     * @return void
     */
    public function test_get_correct_festival_name(string $ticketTypeId, string $nameFestival): void
    {
        $result = $this->festivalService->getFestivalNameByTicketType(new Uuid($ticketTypeId));
        self::assertEquals($nameFestival, $result);
    }

    public function dataProvider(): array
    {
        return [
            [
                TypeTicketsSeeder::ID_FOR_MULTI_FESTIVAL,
                'Solar Systo Togathering '.date('Y').' и на Систо-Осень '. date('Y')
            ],
            [
                TypeTicketsSeeder::ID_FOR_FIRST_WAVE,
                'Solar Systo Togathering '.date('Y')
            ],

            [
                TypeTicketsSeeder::ID_FOR_NEXT_FESTIVAL,
                'Систо-Осень '. date('Y')
            ],
        ];
    }
}
