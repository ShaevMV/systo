<?php

declare(strict_types=1);

namespace App\Models\Ordering;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Ordering\LiveOrderModel
 *
 * @property int         $kilter
 * @property string      $id
 * @property string      $festival_id
 * @property string      $user_id
 * @property string      $ticket_type_id
 * @property string      $types_of_payment_id
 * @property array       $ticket
 * @property string      $status
 * @property float       $price
 * @property float       $discount
 * @property string|null $promo_code
 * @property string      $phone
 * @property string|null $id_buy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|LiveOrderModel newModelQuery()
 * @method static Builder|LiveOrderModel newQuery()
 * @method static Builder|LiveOrderModel query()
 * @mixin Eloquent
 */
final class LiveOrderModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'live_orders';
    protected $table   = self::TABLE;

    protected $casts = [
        'ticket' => 'array',
    ];

    protected $fillable = [
        'id', 'festival_id', 'user_id', 'ticket_type_id', 'types_of_payment_id',
        'ticket', 'status', 'price', 'discount', 'promo_code', 'phone', 'id_buy',
    ];
}
