<?php

namespace App\Models\Ordering\InfoForOrder;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TypesOfPayment
 *
 * @property string $id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TypesOfPaymentModel newModelQuery()
 * @method static Builder|TypesOfPaymentModel newQuery()
 * @method static Builder|TypesOfPaymentModel query()
 * @method static Builder|TypesOfPaymentModel whereCreatedAt($value)
 * @method static Builder|TypesOfPaymentModel whereId($value)
 * @method static Builder|TypesOfPaymentModel whereName($value)
 * @method static Builder|TypesOfPaymentModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class TypesOfPaymentModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'types_of_payment';

    protected $table = self::TABLE;
}
