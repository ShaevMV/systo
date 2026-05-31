<?php

declare(strict_types=1);

namespace App\Models\Option;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Option\OptionPriceModel
 *
 * Волна цены опции. Структурно копирует `TicketTypesPriceModel`,
 * но `price` — INT (рубли целиком, без копеек).
 *
 * @property string $id
 * @property string $option_id
 * @property int $price
 * @property string $before_date
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|OptionPriceModel newModelQuery()
 * @method static Builder|OptionPriceModel newQuery()
 * @method static Builder|OptionPriceModel query()
 * @method static Builder|OptionPriceModel whereId($value)
 * @method static Builder|OptionPriceModel whereOptionId($value)
 * @method static Builder|OptionPriceModel wherePrice($value)
 * @method static Builder|OptionPriceModel whereBeforeDate($value)
 * @mixin Eloquent
 */
class OptionPriceModel extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    public const TABLE = 'option_price';

    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'option_id', 'price', 'before_date',
    ];

    protected $casts = [
        'price' => 'integer',
        'before_date' => 'datetime',
    ];

    public function option(): BelongsTo
    {
        return $this->belongsTo(OptionModel::class, 'option_id', 'id');
    }
}
