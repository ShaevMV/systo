<?php

declare(strict_types=1);

namespace App\Models\Template;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Shared\Infrastructure\Models\HasUuid;

/**
 * App\Models\Template\TemplateModel — редактируемый шаблон письма или PDF-билета.
 *
 * @property string $id
 * @property string $slug
 * @property string $kind
 * @property string $engine
 * @property string $title
 * @property string|null $description
 * @property string $body
 * @property string|null $draft_body
 * @property string|null $compiled_html
 * @property bool $active
 * @property bool $is_system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|TemplateModel query()
 * @method static Builder|TemplateModel whereId($value)
 * @method static Builder|TemplateModel whereSlug($value)
 * @method static Builder|TemplateModel whereKind($value)
 * @method static Builder|TemplateModel whereActive($value)
 * @mixin Eloquent
 */
class TemplateModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'templates';

    protected $table = self::TABLE;

    protected $fillable = [
        'id',
        'slug',
        'kind',
        'engine',
        'title',
        'description',
        'body',
        'draft_body',
        'compiled_html',
        'active',
        'is_system',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_system' => 'boolean',
    ];
}
