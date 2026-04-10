<?php

namespace Tests\Unit\PromoCode\Application\ListPromoCodes;

use Database\Seeders\PromoCodSeeder;
use Database\Seeders\TypeTicketsSeeder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\PromoCode\Application\PromoCodes;
use Tickets\PromoCode\Application\SearchPromoCode\IsCorrectPromoCode;
use Tickets\PromoCode\Dto\LimitPromoCodeDto;
use Tickets\PromoCode\Response\PromoCodeDto;

class GetPromoCodesTest extends TestCase
{
    private PromoCodes $getListPromoCodes;
    private IsCorrectPromoCode $isCorrectPromoCode;

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
        /** @var IsCorrectPromoCode $isCorrectPromoCode */
        $isCorrectPromoCode = $this->app->get(IsCorrectPromoCode::class);
        $this->isCorrectPromoCode = $isCorrectPromoCode;
    }

    public function test_in_correct_get_list(): void
    {
        $result = $this->getListPromoCodes->getList()->getListPromoCode();
        self::assertGreaterThanOrEqual(2, count($result));
        self::assertEquals(new LimitPromoCodeDto(1), $result[PromoCodSeeder::ID_FOR_SYSTO]->getLimit());
        self::assertEquals(new LimitPromoCodeDto(0, 20), $result[PromoCodSeeder::ID_FOR_ILLUNIMISCATA]->getLimit());
    }

    public function test_in_correct_get_item(): void
    {
        $result = $this->getListPromoCodes->getItem(new Uuid(PromoCodSeeder::ID_FOR_SYSTO));
        self::assertInstanceOf(PromoCodeDto::class, $result);
    }

    public function test_in_correct_fine_promoCode(): void
    {
        // illunimiscata — промокод без привязки к типу билета (ticket_type_id = null)
        $ticketTypeId = new Uuid(TypeTicketsSeeder::ID_FOR_FIRST_WAVE);
        $festivalId = new Uuid(\Tickets\Order\OrderTicket\Helpers\FestivalHelper::UUID_FESTIVAL);
        $res = $this->isCorrectPromoCode->findPromoCode(
            'illunimiscata',
            1000,
            $ticketTypeId,
            $festivalId
        );
        // Промокод должен найтись (он активный и без лимита привязки)
        self::assertNotNull($res);
    }
}
