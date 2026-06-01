<?php

declare(strict_types=1);

namespace App\Models\Option;

use App\Models\Festival\TicketTypesModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Option\OptionModel
 *
 * Опция к билету (v2.6.0). См. `.claude/specs/ticket-options.md`.
 *
 * Цена опции — в связанной таблице `option_price` (волны цен, по
 * аналогии с `ticket_type_price`). Описание опции — на pivot
 * `option_ticket_type.description` (зависит от типа билета).
 *
 * @property string $id
 * @property string $name
 * @property bool $active
 * @property string $festival_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|OptionModel newModelQuery()
 * @method static Builder|OptionModel newQuery()
 * @method static Builder|OptionModel query()
 * @method static Builder|OptionModel whereId($value)
 * @method static Builder|OptionModel whereName($value)
 * @method static Builder|OptionModel whereFestivalId($value)
 * @method static Builder|OptionModel whereActive($value)
 * @mixin Eloquent
 */
class OptionModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'options';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'name',
        'active',
        'festival_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Типы билетов, к которым прикреплена опция.
     * Pivot хранит description (зависит от типа билета).
     */
    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            TicketTypesModel::class,
            'option_ticket_type',
            'option_id',
            'ticket_type_id'
        )->withPivot('description');
    }

    /**
     * Все волны цен опции.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(OptionPriceModel::class, 'option_id', 'id');
    }
}
