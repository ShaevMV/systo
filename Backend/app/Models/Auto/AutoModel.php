<?php

declare(strict_types=1);

namespace App\Models\Auto;

use App\Models\Ordering\OrderTicketModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Auto\AutoModel
 *
 * @property string $id
 * @property string $order_ticket_id
 * @property string $number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @method static Builder|AutoModel newModelQuery()
 * @method static Builder|AutoModel newQuery()
 * @method static Builder|AutoModel query()
 * @method static Builder|AutoModel whereId($value)
 * @method static Builder|AutoModel whereOrderTicketId($value)
 * @mixin Eloquent
 */
final class AutoModel extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const TABLE = 'auto';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'order_ticket_id',
        'number',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(OrderTicketModel::class, 'order_ticket_id');
    }
}
