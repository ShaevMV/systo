<?php

declare(strict_types=1);

namespace App\Models\Questionnaire;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Questionnaire\QuestionnaireTypeModel
 *
 * @property string $id
 * @property string $name
 * @property array $questions
 * @property int $active
 * @property int $sort
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|QuestionnaireTypeModel newModelQuery()
 * @method static Builder|QuestionnaireTypeModel newQuery()
 * @method static Builder|QuestionnaireTypeModel query()
 * @method static Builder|QuestionnaireTypeModel whereActive($value)
 * @method static Builder|QuestionnaireTypeModel whereCreatedAt($value)
 * @method static Builder|QuestionnaireTypeModel whereId($value)
 * @method static Builder|QuestionnaireTypeModel whereName($value)
 * @method static Builder|QuestionnaireTypeModel whereQuestions($value)
 * @method static Builder|QuestionnaireTypeModel whereSort($value)
 * @method static Builder|QuestionnaireTypeModel whereUpdatedAt($value)
 * @mixin Eloquent
 */
class QuestionnaireTypeModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'questionnaire_type';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'name',
        'questions',
        'active',
        'sort',
    ];

    protected $casts = [
        'questions' => 'array',
        'active' => 'boolean',
    ];
}
