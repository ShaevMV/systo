<?php

declare(strict_types=1);

namespace App\Models\Location;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Location\LocationModel
 *
 * @property string $id
 * @property string $festival_id
 * @property string $name
 * @property bool $active
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|LocationModel newModelQuery()
 * @method static Builder|LocationModel newQuery()
 * @method static Builder|LocationModel query()
 * @method static Builder|LocationModel whereActive($value)
 * @method static Builder|LocationModel whereCreatedAt($value)
 * @method static Builder|LocationModel whereFestivalId($value)
 * @method static Builder|LocationModel whereId($value)
 * @method static Builder|LocationModel whereName($value)
 * @method static Builder|LocationModel whereSort($value)
 * @method static Builder|LocationModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
final class LocationModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'locations';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'festival_id',
        'name',
        'active',
        'sort',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
