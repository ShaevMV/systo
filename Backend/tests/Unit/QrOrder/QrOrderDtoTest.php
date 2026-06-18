<?php

declare(strict_types=1);

namespace Tests\Unit\QrOrder;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tickets\QrOrder\Dto\QrOrderDto;

/**
 * Разбор расширенного JSON-контракта витрины qr в QrOrderDto:
 * денормализация проекционных полей и валидация обязательных.
 */
class QrOrderDtoTest extends TestCase
{
    private function contract(): array
    {
        return [
            'order_id' => '11111111-1111-1111-1111-111111111111',
            'user' => ['user_id' => '22222222-2222-2222-2222-222222222222', 'name' => 'Иван', 'city' => 'Москва', 'phone' => '+70000000000'],
            'price' => ['price' => 4200, 'discount' => 200, 'total' => 4000],
            'order_data' => [
                'type_order' => 'обычный',
                'festival' => ['id' => '55555555-5555-5555-5555-555555555555', 'title' => 'Систо 2026'],
                'types_of_payment' => ['title' => 'СБП', 'id' => '33333333-3333-3333-3333-333333333333'],
                'comment' => 'коммент',
                'status' => 'оплачен',
                'email' => 'buyer@example.com',
            ],
            'guests' => [
                ['name' => 'Иван Гость', 'email' => 'guest@example.com', 'promocode' => 'SUMMER',
                    'type_ticket' => ['id' => '44444444-4444-4444-4444-444444444444', 'title' => 'Оргвзнос', 'options' => []]],
            ],
        ];
    }

    public function test_maps_projection_fields_and_keeps_full_payload(): void
    {
        // Проекционные поля берутся из вложенных секций контракта, а весь JSON сохраняется в payload.
        $dto = QrOrderDto::fromQrContract($this->contract());

        self::assertSame('11111111-1111-1111-1111-111111111111', $dto->getId()->value());
        self::assertSame('buyer@example.com', $dto->getEmail());
        self::assertSame('оплачен', $dto->getStatus());
        self::assertSame('обычный', $dto->getTypeOrder());
        self::assertSame('Москва', $dto->getCity());
        self::assertSame('+70000000000', $dto->getPhone());
        self::assertSame(4000, $dto->getTotalPrice());
        // festival приходит объектом {id, title} в order_data → в проекцию берём id.
        self::assertSame('55555555-5555-5555-5555-555555555555', $dto->getFestivalId()?->value());
        // payload сохранён целиком (гости на месте).
        self::assertCount(1, $dto->getPayload()['guests']);
    }

    public function test_projects_extended_contract_fields(): void
    {
        // Расширенный контракт qr: external_order_no, payment.method, payment.promo_codes[0],
        // order_data.paid_at — денормализуются в колонки (для списка/фильтра/отчётности),
        // при этом весь JSON (включая payment/buyer/options) остаётся в payload as-is.
        $contract = $this->contract();
        $contract['external_order_no'] = 90909;
        $contract['order_data']['paid_at'] = '2026-06-18T12:45:00+03:00';
        $contract['payment'] = [
            'method' => 'transfer',
            'amount_total' => 4000,
            'promo_codes' => ['OSEN.BUDET', 'SECOND'],
            'transfer' => ['receipt_url' => 'https://qr/r.pdf'],
            'discounts' => [['code' => 'OSEN.BUDET', 'type' => 'fixed']],
        ];

        $dto = QrOrderDto::fromQrContract($contract);

        self::assertSame('90909', $dto->getExternalOrderNo());
        self::assertSame('transfer', $dto->getPaymentMethod());
        self::assertSame('OSEN.BUDET', $dto->getPromoCode()); // первый из promo_codes
        self::assertSame('2026-06-18', $dto->getPaidAt()?->toDateString());
        // Богатые секции лежат в payload целиком (для богатой детали в админке).
        self::assertSame('https://qr/r.pdf', $dto->getPayload()['payment']['transfer']['receipt_url']);
        self::assertCount(1, $dto->getPayload()['payment']['discounts']);
    }

    public function test_extended_fields_are_null_when_absent(): void
    {
        // Старый/минимальный контракт без payment/external_order_no → новые проекции = null.
        $dto = QrOrderDto::fromQrContract($this->contract());

        self::assertNull($dto->getExternalOrderNo());
        self::assertNull($dto->getPaymentMethod());
        self::assertNull($dto->getPromoCode());
        self::assertNull($dto->getPaidAt());
    }

    public function test_festival_id_is_null_when_absent_in_contract(): void
    {
        // Если объекта festival нет в контракте → проекция festival_id = null (колонка nullable).
        $contract = $this->contract();
        unset($contract['order_data']['festival']);

        $dto = QrOrderDto::fromQrContract($contract);
        self::assertNull($dto->getFestivalId());
    }

    public function test_rejects_missing_order_id(): void
    {
        // Без order_id заказ принять нельзя (это и есть общий с org идентификатор).
        $contract = $this->contract();
        unset($contract['order_id']);

        $this->expectException(InvalidArgumentException::class);
        QrOrderDto::fromQrContract($contract);
    }

    public function test_rejects_missing_email(): void
    {
        // Без email некуда отправлять билеты → отказ.
        $contract = $this->contract();
        unset($contract['order_data']['email']);

        $this->expectException(InvalidArgumentException::class);
        QrOrderDto::fromQrContract($contract);
    }
}
