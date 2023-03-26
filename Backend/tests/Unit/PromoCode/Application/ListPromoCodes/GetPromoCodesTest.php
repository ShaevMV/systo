<?php

namespace Tests\Unit\PromoCode\Application\ListPromoCodes;

use App\Http\Requests\CreatePromoCodeRequest;
use Database\Seeders\PromoCodSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Tests\TestCase;
use Throwable;
use Tickets\PromoCode\Application\PromoCodes;
use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class GetPromoCodesTest extends TestCase
{
    private PromoCodes $getListPromoCodes;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var PromoCodes $getListPromoCodes */
        $getListPromoCodes = $this->app->get(PromoCodes::class);
        $this->getListPromoCodes = $getListPromoCodes;
    }

    public function test_in_correct_get_list(): void
    {
        $result = $this->getListPromoCodes->getList()->getListPromoCode();
        self::assertCount(3, $result);

        self::assertEquals(new LimitPromoCodeDto(1), $result[PromoCodSeeder::ID_FOR_SYSTO]->getLimit());
        self::assertEquals(new LimitPromoCodeDto(0, 20), $result[PromoCodSeeder::ID_FOR_ILLUNIMISCATA]->getLimit());
    }

    public function test_in_correct_get_item(): void
    {
        $result = $this->getListPromoCodes->getItem(new Uuid(PromoCodSeeder::ID_FOR_SYSTO));
        self::assertInstanceOf(PromoCodeDto::class, $result);
    }

    /**
     * @throws Throwable
     */
    public function test_in_correct_create(): void
    {
        self::assertTrue($this->getListPromoCodes->createOrUpdatePromoCode([
            'name'=>'spb',
            'discount' => 100,
            'is_percent' => true,
            'active' => true
        ]));
    }

    /**
     * @throws Throwable
     */
    public function test_in_correct_update(): void
    {
        self::assertTrue(
            $this->getListPromoCodes->createOrUpdatePromoCode([
                'id' => PromoCodSeeder::ID_FOR_SYSTO,
                'name' => PromoCodSeeder::NAME_FOR_SYSTO,
                'discount' => 500,
                'active' => true,
                'is_percent' => false,
                'limit' => null,
            ])
        );
    }

}
