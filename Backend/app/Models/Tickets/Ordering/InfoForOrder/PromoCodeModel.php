<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering\InfoForOrder;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 */
class PromoCodeModel extends Model
{
    use HasFactory, HasUuid;

    protected $table = "promo_code";
}
