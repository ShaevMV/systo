<?php

declare(strict_types=1);

namespace App\Models\PromoCode;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\PromoCode\ExternalPromoCodeModel
 *
 * @property string $id
 * @property string|null $order_tickets_id
 * @property string $promocode
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|ExternalPromoCodeModel newModelQuery()
 * @method static Builder|ExternalPromoCodeModel newQuery()
 * @method static Builder|ExternalPromoCodeModel query()
 * @method static Builder|ExternalPromoCodeModel whereCreatedAt($value)
 * @method static Builder|ExternalPromoCodeModel whereId($value)
 * @method static Builder|ExternalPromoCodeModel whereOrderTicketsId($value)
 * @method static Builder|ExternalPromoCodeModel wherePromocode($value)
 * @method static Builder|ExternalPromoCodeModel whereUpdatedAt($value)
 * @mixin Eloquent
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
