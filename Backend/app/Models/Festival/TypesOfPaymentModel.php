<?php

namespace App\Models\Festival;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Festival\TypesOfPaymentModel
 *
 * @property string $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $active
 * @property int $sort
 * @property string $card
 * @property int $is_billing
 * @method static Builder|TypesOfPaymentModel newModelQuery()
 * @method static Builder|TypesOfPaymentModel newQuery()
 * @method static Builder|TypesOfPaymentModel query()
 * @method static Builder|TypesOfPaymentModel whereActive($value)
 * @method static Builder|TypesOfPaymentModel whereCard($value)
 * @method static Builder|TypesOfPaymentModel whereCreatedAt($value)
 * @method static Builder|TypesOfPaymentModel whereId($value)
 * @method static Builder|TypesOfPaymentModel whereIsBilling($value)
 * @method static Builder|TypesOfPaymentModel whereName($value)
 * @method static Builder|TypesOfPaymentModel whereSort($value)
 * @method static Builder|TypesOfPaymentModel whereUpdatedAt($value)
 * @mixin Eloquent
 * @property string|null $user_external_id Связь с продавцом или реализатором
 * @method static Builder|TypesOfPaymentModel whereUserExternalId($value)
 */
class TypesOfPaymentModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'types_of_payment';

    protected $table = self::TABLE;
}
