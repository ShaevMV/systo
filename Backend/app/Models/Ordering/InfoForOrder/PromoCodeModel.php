<?php

declare(strict_types=1);

namespace App\Models\Ordering\InfoForOrder;

use App\Models\Ordering\OrderTicketModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\PromoCod
 *
 * @property string $id
 * @property string $name
 * @property float $discount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PromoCodeModel newModelQuery()
 * @method static Builder|PromoCodeModel newQuery()
 * @method static Builder|PromoCodeModel query()
 * @method static Builder|PromoCodeModel whereCreatedAt($value)
 * @method static Builder|PromoCodeModel whereDiscount($value)
 * @method static Builder|PromoCodeModel whereId($value)
 * @method static Builder|PromoCodeModel whereName($value)
 * @method static Builder|PromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @property int $is_percent
 * @property int $active
 * @property int|null $limit
 * @method static Builder|PromoCodeModel whereActive($value)
 * @method static Builder|PromoCodeModel whereIsPercent($value)
 * @method static Builder|PromoCodeModel whereLimit($value)
 */
class PromoCodeModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = "promo_code";
    protected $table = self::TABLE;


    protected $fillable = [
        'id', 'name', 'discount', 'is_percent', 'active', 'limit'
    ];

    public function orderTickets(): HasMany
    {
        return $this->hasMany(OrderTicketModel::class, 'promo_code');
    }
}
