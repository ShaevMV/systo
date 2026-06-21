<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Плановое расписание смены КПП (PR-A) — заранее составленная сетка.
 *
 * Все рабочие поля сразу в $fillable (иначе через create()/fill() молча не
 * сохранятся — урок count_auto_tickets/is_admin, см. BAZA.md §6).
 * Касты дат — единственный источник форматирования (не Carbon::parse поверх).
 *
 * @property int $id
 * @property string $festival_id
 * @property string|null $kpp_point
 * @property Carbon|null $shift_date
 * @property Carbon|null $planned_start
 * @property Carbon|null $planned_end
 * @property string|null $name
 * @property string $status
 * @property int|null $chief_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ShiftScheduleModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'shift_schedules';

    protected $fillable = [
        'festival_id',
        'kpp_point',
        'shift_date',
        'planned_start',
        'planned_end',
        'name',
        'status',
        'chief_id',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'planned_start' => 'datetime',
        'planned_end' => 'datetime',
        'chief_id' => 'integer',
    ];
}
