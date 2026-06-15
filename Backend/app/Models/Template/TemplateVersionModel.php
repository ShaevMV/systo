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
 * App\Models\Template\TemplateVersionModel — снапшот опубликованного тела шаблона (для отката).
 *
 * Таблица template_versions без updated_at → $timestamps = false; created_at ставим явно в репозитории.
 *
 * @property string $id
 * @property string $template_id
 * @property string $body
 * @property string|null $comment
 * @property string|null $author_id
 * @property Carbon|null $created_at
 * @method static Builder|TemplateVersionModel query()
 * @method static Builder|TemplateVersionModel whereId($value)
 * @method static Builder|TemplateVersionModel whereTemplateId($value)
 * @mixin Eloquent
 */
class TemplateVersionModel extends Model
{
    use HasFactory, HasUuid;

    public const TABLE = 'template_versions';

    protected $table = self::TABLE;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'template_id',
        'body',
        'comment',
        'author_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
