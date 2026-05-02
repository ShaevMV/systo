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
 * App\Models\Ordering\FriendlyOrderModel
 *
 * @property int         $kilter
 * @property string      $id
 * @property string      $festival_id
 * @property string      $user_id
 * @property string      $ticket_type_id
 * @property array       $ticket
 * @property string      $status
 * @property float       $price
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|FriendlyOrderModel newModelQuery()
 * @method static Builder|FriendlyOrderModel newQuery()
 * @method static Builder|FriendlyOrderModel query()
 * @mixin Eloquent
 */
final class FriendlyOrderModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'friendly_orders';
    protected $table   = self::TABLE;

    protected $casts = [
        'ticket' => 'array',
    ];

    protected $fillable = [
        'id', 'festival_id', 'user_id', 'ticket_type_id',
        'ticket', 'status', 'price',
    ];
}
