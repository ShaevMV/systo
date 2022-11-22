<?php

namespace App\Models\Tickets\Ordering\InfoForOrder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tickets\Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Tickets\Ordering\InfoForOrder\Models\TypesOfPayment
 *
 * @property string $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TypesOfPayment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TypesOfPayment extends Model
{
    use HasFactory, HasUuid;

    protected $table = 'types_of_payment';
}
