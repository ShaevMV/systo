<?php

declare(strict_types=1);

namespace Tests\Unit\Integration\Qr;

use PHPUnit\Framework\TestCase;
use Shared\Integration\Rabbit\EventEnvelope;
use Tickets\Integration\Qr\Assembler\QrOrderAssembler;
use Tickets\Integration\Qr\Assembler\QrOrderType;
use Tickets\Integration\Qr\Exception\QrOrderRejectedException;

/**
 * Антикоррупционный слой qr → org: маппинг контракта order.created в AssembledQrOrder
 * и отвержение невалидного контракта (CONTRACT_RFC_v0.md §5–6).
 */
class QrOrderAssemblerTest extends TestCase
{
    private QrOrderAssembler $assembler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assembler = new QrOrderAssembler();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function envelope(array $payload): EventEnvelope
    {
        return new EventEnvelope(
            eventType: 'order.created',
            traceId: 'trace-1',
            idempotencyKey: 'order.qr-1',
            occurredAt: '2026-06-14T12:00:00+00:00',
            source: 'qr',
            payload: $payload,
        );
    }

    private function regularPayload(): array
    {
        return [
            'order' => [
                'qr_order_id' => 'qr-1',
                'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
                'type_order' => 'regular',
                'email' => 'buyer@example.com',
                'comment' => 'привет',
                'user' => ['name' => 'Покупатель', 'city' => 'Москва', 'phone' => '+70000000000'],
                'types_of_payment' => ['id' => '11111111-1111-1111-1111-111111111111', 'title' => 'СБП'],
                'price' => ['price' => 4200, 'discount' => 200, 'total' => 4000],
            ],
            'guests' => [
                [
                    'name' => 'Иван Гость',
                    'email' => 'guest@example.com',
                    'promocode' => 'SUMMER',
                    'ticket_type' => [
                        'id' => '22222222-2222-2222-2222-222222222222',
                        'title' => 'Оргвзнос',
                        'options' => [
                            ['option_id' => '33333333-3333-3333-3333-333333333333', 'qty' => 2, 'title' => 'Саженец'],
                        ],
                    ],
                    'price' => ['base_price' => 4200, 'options_sum' => 0, 'discount' => 200, 'total' => 4000],
                ],
            ],
        ];
    }

    public function test_assembles_valid_regular_order(): void
    {
        // Полный обычный заказ: проверяем маппинг шапки (тип, фестиваль, получатель, оплата,
        // итоговая цена) и первого гостя (ФИО, email, тип билета, промокод, опции, цена).
        $order = $this->assembler->assemble($this->envelope($this->regularPayload()));

        self::assertSame(QrOrderType::REGULAR, $order->type->value);
        self::assertSame('qr-1', $order->qrOrderId);
        self::assertSame('9d679bcf-b438-4ddb-ac04-023fa9bff4b8', $order->festivalId);
        self::assertSame('buyer@example.com', $order->recipientEmail);
        self::assertSame('Покупатель', $order->recipientName);
        self::assertSame('11111111-1111-1111-1111-111111111111', $order->typesOfPaymentId);
        self::assertNull($order->friendlyId);
        self::assertNull($order->curatorId);
        self::assertSame(4000, $order->declaredTotal);
        self::assertCount(1, $order->guests);

        $guest = $order->guests[0];
        self::assertSame('Иван Гость', $guest->value);
        self::assertSame('guest@example.com', $guest->email);
        self::assertSame('22222222-2222-2222-2222-222222222222', $guest->ticketTypeId);
        self::assertSame('SUMMER', $guest->promoCode);
        self::assertSame(4000, $guest->declaredTotal);
        self::assertCount(1, $guest->options);
        self::assertSame('33333333-3333-3333-3333-333333333333', $guest->options[0]->optionId->value());
        self::assertSame(2, $guest->options[0]->qty);
    }

    public function test_assembles_friendly_order_with_pusher_fallback(): void
    {
        // Дружеский заказ без блока friendly, но с pusher: friendly_id берётся из pusher.id (fallback).
        $payload = $this->regularPayload();
        $payload['order']['type_order'] = 'friendly';
        $payload['order']['pusher'] = ['id' => '44444444-4444-4444-4444-444444444444', 'name' => 'Пушер'];

        $order = $this->assembler->assemble($this->envelope($payload));

        self::assertTrue($order->type->isFriendly());
        self::assertSame('44444444-4444-4444-4444-444444444444', $order->friendlyId);
    }

    public function test_assembles_list_order_without_ticket_type_or_price(): void
    {
        // Заказ-список: куратор+локация обязательны, оплаты/типа билета/цены нет (списки бесплатны).
        $payload = [
            'order' => [
                'qr_order_id' => 'qr-list-1',
                'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
                'type_order' => 'list',
                'email' => 'recipient@example.com',
                'curator' => ['id' => '55555555-5555-5555-5555-555555555555', 'name' => 'Куратор'],
                'location' => ['id' => '66666666-6666-6666-6666-666666666666', 'title' => 'Сцена'],
            ],
            'guests' => [
                ['name' => 'Гость 1', 'email' => 'g1@example.com'],
            ],
        ];

        $order = $this->assembler->assemble($this->envelope($payload));

        self::assertTrue($order->type->isList());
        self::assertSame('55555555-5555-5555-5555-555555555555', $order->curatorId);
        self::assertSame('66666666-6666-6666-6666-666666666666', $order->locationId);
        self::assertNull($order->typesOfPaymentId);
        self::assertSame(0, $order->declaredTotal);
        self::assertNull($order->guests[0]->ticketTypeId);
        self::assertSame([], $order->guests[0]->options);
        self::assertSame(0, $order->guests[0]->declaredTotal);
    }

    public function test_live_order_carries_number_and_type(): void
    {
        // Живой билет: тип live распознан, номер живого билета и тип билета донесены до гостя.
        $payload = $this->regularPayload();
        $payload['order']['type_order'] = 'live';
        $payload['guests'][0]['number'] = '777';

        $order = $this->assembler->assemble($this->envelope($payload));

        self::assertTrue($order->type->isLive());
        self::assertSame('777', $order->guests[0]->liveNumber);
        self::assertSame('22222222-2222-2222-2222-222222222222', $order->guests[0]->ticketTypeId);
    }

    public function test_rejects_unknown_type_order(): void
    {
        // Русский текст вместо машинного кода типа → отказ (qr обязан слать regular/friendly/live/list).
        $payload = $this->regularPayload();
        $payload['order']['type_order'] = 'оплачен';

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }

    public function test_rejects_missing_festival_id(): void
    {
        // Нет festival_id на уровне заказа → отказ (без него заказ в org не создать).
        $payload = $this->regularPayload();
        unset($payload['order']['festival_id']);

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }

    public function test_rejects_invalid_guest_email(): void
    {
        // Невалидный email гостя → отказ (на email уходит анкета, пустой/битый недопустим).
        $payload = $this->regularPayload();
        $payload['guests'][0]['email'] = 'не-email';

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }

    public function test_rejects_empty_guests(): void
    {
        // Пустой массив гостей → отказ (заказ без гостей бессмысленен).
        $payload = $this->regularPayload();
        $payload['guests'] = [];

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }

    public function test_rejects_bad_option_qty(): void
    {
        // Некорректный qty опции (0): RawGuestOptionInput бросает InvalidArgumentException (qty<1),
        // а ассемблер оборачивает её в перманентный QrOrderRejectedException.
        $payload = $this->regularPayload();
        $payload['guests'][0]['ticket_type']['options'] = [
            ['option_id' => '33333333-3333-3333-3333-333333333333', 'qty' => 0],
        ];

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }

    public function test_rejects_non_list_order_without_ticket_type(): void
    {
        // Для не-списочного заказа тип билета у гостя обязателен → без него отказ.
        $payload = $this->regularPayload();
        unset($payload['guests'][0]['ticket_type']);

        $this->expectException(QrOrderRejectedException::class);
        $this->assembler->assemble($this->envelope($payload));
    }
}
