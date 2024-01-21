<?php

declare(strict_types=1);

namespace App\Models\Ordering\InfoForOrder;

use App\Models\Festival\FestivalModel;
use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\TicketTypeFestivalModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TicketTypesModel newModelQuery()
 * @method static Builder|TicketTypesModel newQuery()
 * @method static Builder|TicketTypesModel query()
 * @method static Builder|TicketTypesModel whereCreatedAt($value)
 * @method static Builder|TicketTypesModel whereGroupLimit($value)
 * @method static Builder|TicketTypesModel whereId($value)
 * @method static Builder|TicketTypesModel whereName($value)
 * @method static Builder|TicketTypesModel wherePrice($value)
 * @method static Builder|TicketTypesModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Ordering\InfoForOrder\TicketTypesPriceModel[] $ticketTypePrice
 * @property-read int|null $ticket_type_price_count
 * @property string $festival_id
 * @property int $sort
 * @property int $active
 * @method static Builder|TicketTypesModel whereActive($value)
 * @method static Builder|TicketTypesModel whereFestivalId($value)
 * @method static Builder|TicketTypesModel whereSort($value)
 */
class TicketTypesModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'ticket_type';

    protected $table = self::TABLE;


    public function ticketTypePrice(): HasMany
    {
        return $this->hasMany(TicketTypesPriceModel::class, 'ticket_type_id');
    }


    public function festival(): BelongsToMany
    {
        return $this->belongsToMany(
            FestivalModel::class,
            TicketTypeFestivalModel::TABLE,
            'ticket_type_id',
            'festival_id'
        );
    }
}
