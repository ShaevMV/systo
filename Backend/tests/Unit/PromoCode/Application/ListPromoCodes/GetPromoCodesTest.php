<?php

namespace Tests\Unit\PromoCode\Application\ListPromoCodes;

use Database\Seeders\PromoCodSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Tickets\PromoCode\Application\ListPromoCodes\GetPromoCodes;
use Tickets\PromoCode\Dto\LimitPromoCodeDto;

class GetPromoCodesTest extends TestCase
{
    private GetPromoCodes $getListPromoCodes;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var GetPromoCodes $getListPromoCodes */
        $getListPromoCodes = $this->app->get(GetPromoCodes::class);
        $this->getListPromoCodes = $getListPromoCodes;
    }

    public function test_is_correct_get_list(): void
    {
        $result = $this->getListPromoCodes->getList()->getListPromoCode();
        self::assertCount(3, $result);

        self::assertEquals(new LimitPromoCodeDto(1), $result[PromoCodSeeder::ID_FOR_SYSTO]->getLimit());
        self::assertEquals(new LimitPromoCodeDto(0, 20), $result[PromoCodSeeder::ID_FOR_ILLUNIMISCATA]->getLimit());
    }
}
