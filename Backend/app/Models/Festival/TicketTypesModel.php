<?php

declare(strict_types=1);

namespace App\Models\Festival;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Festival\TicketTypesModel
 *
 * @property string $id
 * @property string $name
 * @property float $price
 * @property int|null $groupLimit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $sort
 * @property int $active
 * @property int $is_live_ticket
 * @property int $is_parking
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\FestivalModel[] $festivals
 * @property-read int|null $festivals_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Festival\TicketTypesPriceModel[] $ticketTypePrice
 * @property-read int|null $ticket_type_price_count
 * @method static Builder|TicketTypesModel newModelQuery()
 * @method static Builder|TicketTypesModel newQuery()
 * @method static Builder|TicketTypesModel query()
 * @method static Builder|TicketTypesModel whereActive($value)
 * @method static Builder|TicketTypesModel whereCreatedAt($value)
 * @method static Builder|TicketTypesModel whereGroupLimit($value)
 * @method static Builder|TicketTypesModel whereId($value)
 * @method static Builder|TicketTypesModel whereIsLiveTicket($value)
 * @method static Builder|TicketTypesModel whereIsParking($value)
 * @method static Builder|TicketTypesModel whereName($value)
 * @method static Builder|TicketTypesModel wherePrice($value)
 * @method static Builder|TicketTypesModel whereSort($value)
 * @method static Builder|TicketTypesModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TicketTypesModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'ticket_type';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'name',
        'price',
        'created_at',
        'updated_at',
        'sort',
        'active',
        'is_live_ticket',
        'is_parking',
        'groupLimit',
        'questionnaire_type_id',
    ];

    public function ticketTypePrice(): HasMany
    {
        return $this->hasMany(TicketTypesPriceModel::class, 'ticket_type_id');
    }

    public function festivals(): BelongsToMany
    {
        return $this->belongsToMany(
            FestivalModel::class,
            TicketTypeFestivalModel::TABLE,
            'ticket_type_id',
            'festival_id'
        )->withPivot(['email', 'pdf']);
    }

    public function questionnaireType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Questionnaire\QuestionnaireTypeModel::class, 'questionnaire_type_id');
    }
}
