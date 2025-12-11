<?php

declare(strict_types=1);

namespace App\Models\Ordering\InfoForOrder;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Ordering\InfoForOrder\ExternalPromoCodeModel
 *
 * @property string $id
 * @property string $promocode
 * @property string|null $order_tickets_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|PromoCodeModel newModelQuery()
 * @method static Builder|PromoCodeModel newQuery()
 * @method static Builder|PromoCodeModel query()
 * @method static Builder|PromoCodeModel whereId($value)
 * @method static Builder|PromoCodeModel whereOrderTicketsId($value)
 * @method static Builder|PromoCodeModel wherePromocode($value)
 * @method static Builder|PromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @method static Builder|ExternalPromoCodeModel whereCreatedAt($value)
 */
class ExternalPromoCodeModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = "external_promocode";
    protected $table = self::TABLE;


    protected $fillable = [
        'id', 'order_tickets_id', 'promocode'
    ];
}
