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
 * @property string $name
 * @property string|null $description
 * @property string|null $questionnaire_type_id
 * @property string $festival_id
 * @property string|null $email_template
 * @property string|null $pdf_template
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|LocationModel newModelQuery()
 * @method static Builder|LocationModel newQuery()
 * @method static Builder|LocationModel query()
 * @method static Builder|LocationModel whereId($value)
 * @method static Builder|LocationModel whereName($value)
 * @method static Builder|LocationModel whereFestivalId($value)
 * @method static Builder|LocationModel whereActive($value)
 * @mixin Eloquent
 */
class LocationModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'locations';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'name',
        'description',
        'questionnaire_type_id',
        'festival_id',
        'email_template',
        'pdf_template',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
