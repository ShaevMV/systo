<?php

declare(strict_types=1);

namespace App\Models\BazaDelivery;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * Трекинг доставки билета в Baza. Текущий статус доставки; таймлайн всех попыток — в domain_history.
 *
 * @property string $id
 * @property string $ticket_id
 * @property string|null $order_id
 * @property string $target
 * @property string $status
 * @property int $attempts
 * @property string|null $error
 * @property string|null $name
 * @property string|null $email
 * @property int|null $number
 * @property string|null $festival_id
 * @property string $source
 * @property Carbon|null $delivered_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|BazaDeliveryModel query()
 * @method static Builder|BazaDeliveryModel whereId($value)
 * @mixin Eloquent
 */
class BazaDeliveryModel extends Model
{
    use HasUuid;

    public const TABLE = 'baza_deliveries';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'ticket_id',
        'order_id',
        'target',
        'status',
        'attempts',
        'error',
        'name',
        'email',
        'number',
        'festival_id',
        'source',
        'delivered_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'number' => 'integer',
        'delivered_at' => 'datetime',
    ];
}
