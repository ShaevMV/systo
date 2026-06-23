<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Реестр фестивалей на Vhod (TD-48, PR-1) — read-реплика каталога org + локальный
 * флаг `active_for_kpp`.
 *
 * `id` — uuid из org (не автоинкремент), поэтому incrementing=false / keyType=string.
 * Все рабочие поля в $fillable (иначе через create()/fill() молча не сохранятся —
 * урок count_auto_tickets/is_admin, BAZA.md §6). Касты — единственный источник
 * форматирования.
 *
 * @property string $id
 * @property string $name
 * @property int|null $year
 * @property bool $active
 * @property bool $active_for_kpp
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class FestivalModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'festivals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'year',
        'active',
        'active_for_kpp',
    ];

    protected $casts = [
        'year' => 'integer',
        'active' => 'boolean',
        'active_for_kpp' => 'boolean',
    ];
}
