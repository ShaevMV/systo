<?php

namespace App\Models\Ordering\InfoForOrder;

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
 * @property string|null $ticket_type_id Связь с типом билета
 * @method static Builder|TypesOfPaymentModel whereTicketTypeId($value)
 */
class TypesOfPaymentModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'types_of_payment';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'name',
        'created_at',
        'updated_at',
        'active',
        'sort',
        'card',
        'is_billing',
        'user_external_id',
        'ticket_type_id',
    ];
}
