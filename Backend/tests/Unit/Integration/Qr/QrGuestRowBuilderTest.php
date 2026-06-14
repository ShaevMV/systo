<?php

declare(strict_types=1);

namespace Tests\Unit\Integration\Qr;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Integration\Qr\Assembler\AssembledQrGuest;
use Tickets\Integration\Qr\Assembler\AssembledQrOrder;
use Tickets\Integration\Qr\Assembler\QrOrderType;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;
use Tickets\Integration\Qr\QrGuestRowBuilder;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Repositories\OptionRepositoryInterface;
use Tickets\Order\OrderTicket\Application\Pricing\Dto\RawGuestOptionInput;

/**
 * Сборка строк гостя для OrderTicketDto из AssembledQrOrder: цена-снимок от qr (Р2),
 * разворот опций из каталога org, анти-фрод на неизвестную опцию.
 */
class QrGuestRowBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const FEST = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';
    private const TT   = '22222222-2222-2222-2222-222222222222';
    private const PAY  = '11111111-1111-1111-1111-111111111111';
    private const OPT  = '33333333-3333-3333-3333-333333333333';

    /**
     * @param AssembledQrGuest[] $guests
     */
    private function order(array $guests, string $type = QrOrderType::REGULAR): AssembledQrOrder
    {
        return new AssembledQrOrder(
            type: new QrOrderType($type),
            qrOrderId: 'qr-1',
            festivalId: self::FEST,
            recipientEmail: 'buyer@example.com',
            recipientName: 'Покупатель',
            recipientCity: 'Москва',
            recipientPhone: '+70000000000',
            typesOfPaymentId: self::PAY,
            friendlyId: null,
            curatorId: null,
            locationId: null,
            comment: null,
            declaredPrice: 4200,
            declaredDiscount: 200,
            declaredTotal: 4000,
            guests: $guests,
        );
    }

    public function test_builds_regular_row_with_qr_price(): void
    {
        // Обычный гость без опций: строка должна нести цену-снимок именно от qr (Р2),
        // тип билета/фестиваль/промокод — как пришли, флаг live = false, опций нет.
        $repo = Mockery::mock(OptionRepositoryInterface::class);
        $builder = new QrGuestRowBuilder($repo);

        $guest = new AssembledQrGuest(
            value: 'Иван Гость',
            email: 'g@example.com',
            ticketTypeId: self::TT,
            options: [],
            promoCode: 'SUMMER',
            liveNumber: null,
            declaredBasePrice: 4200,
            declaredOptionsSum: 0,
            declaredDiscount: 200,
            declaredTotal: 4000,
        );

        $rows = $builder->build($this->order([$guest]));

        self::assertCount(1, $rows);
        $row = $rows[0];
        self::assertSame('Иван Гость', $row['value']);
        self::assertSame('g@example.com', $row['email']);
        self::assertSame(self::TT, $row['ticket_type_id']);
        self::assertSame(self::FEST, $row['festival_id']);
        self::assertSame('SUMMER', $row['promo_code']);
        self::assertFalse($row['is_live_ticket']);
        self::assertNull($row['number']);
        self::assertSame([], $row['options']);
        self::assertSame(
            ['base_price' => 4200, 'options_sum' => 0, 'discount' => 200],
            $row['price_snapshot'],
        );
        // id строки сгенерирован и является валидным UUID (конструктор бросит при невалидном).
        self::assertInstanceOf(Uuid::class, new Uuid($row['id']));
    }

    public function test_expands_options_from_catalog_by_qty(): void
    {
        // qty=2 из qr должно развернуться в 2 одинаковых снимка опции,
        // имя и цена которых берутся из каталога org (а не из payload qr).
        $repo = Mockery::mock(OptionRepositoryInterface::class);
        $repo->shouldReceive('getActiveOptionsForTicketType')
            ->once()
            ->andReturn([new OptionForTicketTypeView(new Uuid(self::OPT), 'Саженец', 500, null, true)]);

        $builder = new QrGuestRowBuilder($repo);

        $guest = new AssembledQrGuest(
            value: 'Иван',
            email: 'g@example.com',
            ticketTypeId: self::TT,
            options: [new RawGuestOptionInput(new Uuid(self::OPT), 2)],
            promoCode: null,
            liveNumber: null,
            declaredBasePrice: 4200,
            declaredOptionsSum: 1000,
            declaredDiscount: 0,
            declaredTotal: 5200,
        );

        $rows = $builder->build($this->order([$guest]));

        $options = $rows[0]['options'];
        self::assertCount(2, $options);
        self::assertSame(['option_id' => self::OPT, 'name' => 'Саженец', 'price' => 500], $options[0]);
        self::assertSame(['option_id' => self::OPT, 'name' => 'Саженец', 'price' => 500], $options[1]);
    }

    public function test_rejects_unknown_option(): void
    {
        // Анти-фрод: опция, которой нет в каталоге org для этого типа билета,
        // должна привести к перманентному отказу (qr не может «подсунуть» левую опцию).
        $repo = Mockery::mock(OptionRepositoryInterface::class);
        $repo->shouldReceive('getActiveOptionsForTicketType')->once()->andReturn([]); // пустой каталог

        $builder = new QrGuestRowBuilder($repo);

        $guest = new AssembledQrGuest(
            value: 'Иван',
            email: 'g@example.com',
            ticketTypeId: self::TT,
            options: [new RawGuestOptionInput(new Uuid(self::OPT), 1)],
            promoCode: null,
            liveNumber: null,
            declaredBasePrice: 4200,
            declaredOptionsSum: 0,
            declaredDiscount: 0,
            declaredTotal: 4200,
        );

        $this->expectException(QrOrderRejectedException::class);
        $builder->build($this->order([$guest]));
    }

    public function test_live_order_sets_flag_and_number(): void
    {
        // Живой билет: флаг is_live_ticket=true, а номер из qr приводится к int.
        $repo = Mockery::mock(OptionRepositoryInterface::class);
        $builder = new QrGuestRowBuilder($repo);

        $guest = new AssembledQrGuest(
            value: 'Иван',
            email: 'g@example.com',
            ticketTypeId: self::TT,
            options: [],
            promoCode: null,
            liveNumber: '777',
            declaredBasePrice: 3800,
            declaredOptionsSum: 0,
            declaredDiscount: 0,
            declaredTotal: 3800,
        );

        $rows = $builder->build($this->order([$guest], QrOrderType::LIVE));

        self::assertTrue($rows[0]['is_live_ticket']);
        self::assertSame(777, $rows[0]['number']);
    }
}
