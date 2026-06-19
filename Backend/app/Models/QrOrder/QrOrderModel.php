<?php

declare(strict_types=1);

namespace App\Models\QrOrder;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\QrOrder\QrOrderModel — входящий заказ от витрины qr.
 *
 * @property string $id
 * @property string $email
 * @property string $status
 * @property string|null $festival_id
 * @property string|null $type_order
 * @property string|null $city
 * @property string|null $phone
 * @property int $total_price
 * @property array $payload
 * @property Carbon|null $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|QrOrderModel query()
 * @method static Builder|QrOrderModel whereId($value)
 *
 * @mixin Eloquent
 */
class QrOrderModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'qr_orders';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'email',
        'status',
        'festival_id',
        'type_order',
        'city',
        'phone',
        'total_price',
        'payload',
        'issued_at',
        'external_order_no',
        'payment_method',
        'promo_code',
        'paid_at',
        'buyer_fio',
        'festival_title',
    ];

    protected $casts = [
        'payload' => 'array',      // единственный источник кодирования JSON (правило №11)
        'total_price' => 'integer',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
}
