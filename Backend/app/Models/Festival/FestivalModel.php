<?php

declare(strict_types=1);

namespace App\Models\Festival;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;


/**
 * App\Models\Festival\FestivalModel
 *
 * @property string $id
 * @property string $year
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $name
 * @property string|null $view
 * @method static Builder|FestivalModel newModelQuery()
 * @method static Builder|FestivalModel newQuery()
 * @method static Builder|FestivalModel query()
 * @method static Builder|FestivalModel whereActive($value)
 * @method static Builder|FestivalModel whereCreatedAt($value)
 * @method static Builder|FestivalModel whereId($value)
 * @method static Builder|FestivalModel whereName($value)
 * @method static Builder|FestivalModel whereUpdatedAt($value)
 * @method static Builder|FestivalModel whereView($value)
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
