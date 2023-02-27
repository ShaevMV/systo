<?php

declare(strict_types=1);

namespace App\Models\Ordering\InfoForOrder;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Ordering\InfoForOrder\TicketTypesPriceModel
 *
 * @property string $id
 * @property string $ticket_type_id
 * @property float $price
 * @property string $before_date
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketTypesPriceModel newModelQuery()
 * @method static Builder|TicketTypesPriceModel newQuery()
 * @method static Builder|TicketTypesPriceModel query()
 * @method static Builder|TicketTypesPriceModel whereBeforeDate($value)
 * @method static Builder|TicketTypesPriceModel whereCreatedAt($value)
 * @method static Builder|TicketTypesPriceModel whereDeletedAt($value)
 * @method static Builder|TicketTypesPriceModel whereId($value)
 * @method static Builder|TicketTypesPriceModel wherePrice($value)
 * @method static Builder|TicketTypesPriceModel whereTicketTypeId($value)
 * @method static Builder|TicketTypesPriceModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TicketTypesPriceModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'ticket_type_price';

    protected $table = self::TABLE;


    protected $fillable = [
        'id', 'before_date', 'price', 'ticket_type_id',
    ];
}
