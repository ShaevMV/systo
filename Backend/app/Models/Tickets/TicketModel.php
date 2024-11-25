<?php

namespace App\Models\Tickets;

use App\Models\Ordering\OrderTicketModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\TicketModel
 *
 * @method static Builder|TicketModel newModelQuery()
 * @method static Builder|TicketModel newQuery()
 * @method static Builder|TicketModel query()
 * @mixin Eloquent
 * @property int $id
 * @property string $order_ticket_id
 * @property string $festival_id
 * @property int $number
 * @property string $name
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketModel whereCreatedAt($value)
 * @method static Builder|TicketModel whereId($value)
 * @method static Builder|TicketModel whereName($value)
 * @method static Builder|TicketModel whereNumber($value)
 * @method static Builder|TicketModel whereOrderTicketId($value)
 * @method static Builder|TicketModel whereStatus($value)
 * @method static Builder|TicketModel whereUpdatedAt($value)
 * @property Carbon|null $deleted_at
 * @method static \Illuminate\Database\Query\Builder|TicketModel onlyTrashed()
 * @method static Builder|TicketModel whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|TicketModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|TicketModel withoutTrashed()
 * @property int $kilter
 * @method static Builder|TicketModel whereKilter($value)
 */
class TicketModel extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    public const TABLE = 'tickets';
    protected $table = self::TABLE;


    protected $fillable = [
        'id', 'order_ticket_id', 'number', 'name', 'status'
    ];

    public function orderTicket():HasOne
    {
        return $this->hasOne(
            OrderTicketModel::class,
            'id',
            'order_ticket_id',
        );
    }
}
