<?php

declare(strict_types=1);

namespace App\Models\Tickets\Ordering\InfoForOrder\Models;

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
 * @method static Builder|PromoCode newModelQuery()
 * @method static Builder|PromoCode newQuery()
 * @method static Builder|PromoCode query()
 * @method static Builder|PromoCode whereCreatedAt($value)
 * @method static Builder|PromoCode whereDiscount($value)
 * @method static Builder|PromoCode whereId($value)
 * @method static Builder|PromoCode whereName($value)
 * @method static Builder|PromoCode whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PromoCode extends Model
{
    use HasFactory, HasUuid;

    protected $table = "promo_code";
}
