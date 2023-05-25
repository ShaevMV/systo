<?php

declare(strict_types=1);

namespace App\Models\Festival;

use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tickets\Shared\Infrastructure\Models\HasUuid;


/**
 * App\Models\Festival\FestivalModel
 *
 * @property string $id
 * @property string $year
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @method static Builder|FestivalModel newModelQuery()
 * @method static Builder|FestivalModel newQuery()
 * @method static Builder|FestivalModel query()
 * @method static Builder|FestivalModel whereActive($value)
 * @method static Builder|FestivalModel whereCreatedAt($value)
 * @method static Builder|FestivalModel whereId($value)
 * @method static Builder|FestivalModel whereName($value)
 * @method static Builder|FestivalModel whereUpdatedAt($value)
 * @method static Builder|FestivalModel whereYear($value)
 * @mixin Eloquent
 */
final class FestivalModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'festivals';
    protected $table = self::TABLE;

    protected $fillable = [
        'id', 'year', 'active', 'name'
    ];

}
