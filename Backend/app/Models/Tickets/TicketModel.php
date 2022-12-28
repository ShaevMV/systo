<?php

namespace App\Models\Tickets;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Tickets\TicketModel
 *
 * @method static Builder|TicketModel newModelQuery()
 * @method static Builder|TicketModel newQuery()
 * @method static Builder|TicketModel query()
 * @mixin Eloquent
 * @property int $id
 * @property string $order_ticket_id
 * @property int $number
 * @property string $name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder|TicketModel whereCreatedAt($value)
 * @method static Builder|TicketModel whereId($value)
 * @method static Builder|TicketModel whereName($value)
 * @method static Builder|TicketModel whereNumber($value)
 * @method static Builder|TicketModel whereOrderTicketId($value)
 * @method static Builder|TicketModel whereStatus($value)
 * @method static Builder|TicketModel whereUpdatedAt($value)
 */
class TicketModel extends Model
{
    use HasFactory;

    public const TABLE = 'tickets';
    protected $table = self::TABLE;


    protected $fillable = [
        'id', 'order_ticket_id', 'number', 'name', 'status'
    ];


}
