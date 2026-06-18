<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Облегчённая проекция qr-заказа для списка админки (без payload — он тяжёлый и нужен
 * только в деталях, см. getItem). Поля совпадают с проекцией qr_orders (snake_case),
 * чтобы фронт читал список и деталь в едином формате.
 */
class QrOrderItemForListResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $status,
        protected ?Uuid $festival_id,
        protected ?string $type_order,
        protected ?string $city,
        protected ?string $phone,
        protected int $total_price,
        protected ?string $issued_at,
        protected ?string $created_at,
        protected ?string $external_order_no = null,
        protected ?string $payment_method = null,
        protected ?string $promo_code = null,
        protected ?string $paid_at = null,
    ) {}

    /**
     * Сборка из строки БД (qr_orders.*) — даты уже ISO-строки (Eloquent-каст), не оборачиваем
     * в Carbon повторно (правило единого формата данных).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid((string) $data['id']),
            (string) ($data['email'] ?? ''),
            (string) ($data['status'] ?? ''),
            empty($data['festival_id']) ? null : new Uuid((string) $data['festival_id']),
            isset($data['type_order']) ? (string) $data['type_order'] : null,
            isset($data['city']) ? (string) $data['city'] : null,
            isset($data['phone']) ? (string) $data['phone'] : null,
            (int) ($data['total_price'] ?? 0),
            isset($data['issued_at']) ? (string) $data['issued_at'] : null,
            isset($data['created_at']) ? (string) $data['created_at'] : null,
            isset($data['external_order_no']) ? (string) $data['external_order_no'] : null,
            isset($data['payment_method']) ? (string) $data['payment_method'] : null,
            isset($data['promo_code']) ? (string) $data['promo_code'] : null,
            isset($data['paid_at']) ? (string) $data['paid_at'] : null,
        );
    }
}
