<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Dto;

use Carbon\Carbon;
use InvalidArgumentException;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Uuid;

/**
 * Заказ, пришедший от витрины qr. Источник истины — `payload` (весь контракт as-is);
 * остальные поля — денормализованная проекция для фильтрации (см. миграцию qr_orders).
 *
 * id заказа qr РАВЕН id заказа org (маппинг не нужен).
 */
class QrOrderDto extends AbstractionEntity implements Response
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $status,
        protected ?Uuid $festival_id,
        protected ?string $type_order,
        protected ?string $city,
        protected ?string $phone,
        protected int $total_price,
        protected array $payload,
        protected ?Carbon $issued_at = null,
        protected ?Carbon $created_at = null,
        protected ?Carbon $updated_at = null,
        protected ?string $external_order_no = null,
        protected ?string $payment_method = null,
        protected ?string $promo_code = null,
        protected ?Carbon $paid_at = null,
    ) {}

    /**
     * Сборка из строки БД (qr_orders.*).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            $data['email'],
            $data['status'],
            empty($data['festival_id']) ? null : new Uuid($data['festival_id']),
            $data['type_order'] ?? null,
            $data['city'] ?? null,
            $data['phone'] ?? null,
            (int) ($data['total_price'] ?? 0),
            is_array($data['payload'] ?? null) ? $data['payload'] : (json_decode((string) ($data['payload'] ?? '[]'), true) ?? []),
            empty($data['issued_at']) ? null : new Carbon($data['issued_at']),
            empty($data['created_at']) ? null : new Carbon($data['created_at']),
            empty($data['updated_at']) ? null : new Carbon($data['updated_at']),
            external_order_no: isset($data['external_order_no']) ? (string) $data['external_order_no'] : null,
            payment_method: isset($data['payment_method']) ? (string) $data['payment_method'] : null,
            promo_code: isset($data['promo_code']) ? (string) $data['promo_code'] : null,
            paid_at: empty($data['paid_at']) ? null : new Carbon($data['paid_at']),
        );
    }

    /**
     * Сборка из расширенного JSON-контракта витрины qr (см. CONTRACT JSON в задаче).
     * Проекционные поля денормализуются из вложенных секций, весь JSON кладётся в payload.
     *
     * Поле `guests[].telegram` (per-guest): обязательно на стороне qr, на org валидируется мягко
     * (пустое — пропускается). Используется шагом SendTelegramStep для уведомления в бот;
     * хранится в payload, в проекцию qr_orders НЕ выносится (по нему не фильтруем).
     *
     * @param  array<string, mixed>  $json
     *
     * @throws InvalidArgumentException при отсутствии обязательных полей
     */
    public static function fromQrContract(array $json): self
    {
        $orderId = $json['order_id'] ?? null;
        if (! is_string($orderId) || $orderId === '') {
            throw new InvalidArgumentException('qr-контракт: отсутствует "order_id"');
        }

        $orderData = is_array($json['order_data'] ?? null) ? $json['order_data'] : [];
        $user = is_array($json['user'] ?? null) ? $json['user'] : [];
        $price = is_array($json['price'] ?? null) ? $json['price'] : [];

        $email = $orderData['email'] ?? null;
        if (! is_string($email) || $email === '') {
            throw new InvalidArgumentException('qr-контракт: отсутствует "order_data.email" (куда отправлять билеты)');
        }

        // Фестиваль приходит объектом order_data.festival = {id, title} (как types_of_payment/location).
        $festival = is_array($orderData['festival'] ?? null) ? $orderData['festival'] : [];
        $festivalId = $festival['id'] ?? ($orderData['festival_id'] ?? ($json['festival_id'] ?? null));

        // Доп. проекция расширенного контракта (хранится и в payload; здесь — денормализация в колонки).
        $payment = is_array($json['payment'] ?? null) ? $json['payment'] : [];
        $promoCodes = is_array($payment['promo_codes'] ?? null) ? $payment['promo_codes'] : [];

        return new self(
            new Uuid($orderId),
            $email,
            (string) ($orderData['status'] ?? 'создан'),
            empty($festivalId) ? null : new Uuid((string) $festivalId),
            isset($orderData['type_order']) ? (string) $orderData['type_order'] : null,
            isset($user['city']) ? (string) $user['city'] : null,
            isset($user['phone']) ? (string) $user['phone'] : null,
            (int) ($price['total'] ?? 0),
            $json,
            external_order_no: isset($json['external_order_no']) ? (string) $json['external_order_no'] : null,
            payment_method: isset($payment['method']) ? (string) $payment['method'] : null,
            promo_code: ! empty($promoCodes) ? (string) $promoCodes[0] : null,
            paid_at: empty($orderData['paid_at']) ? null : new Carbon((string) $orderData['paid_at']),
        );
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festival_id;
    }

    public function getTypeOrder(): ?string
    {
        return $this->type_order;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getTotalPrice(): int
    {
        return $this->total_price;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getIssuedAt(): ?Carbon
    {
        return $this->issued_at;
    }

    public function getExternalOrderNo(): ?string
    {
        return $this->external_order_no;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function getPromoCode(): ?string
    {
        return $this->promo_code;
    }

    public function getPaidAt(): ?Carbon
    {
        return $this->paid_at;
    }
}
