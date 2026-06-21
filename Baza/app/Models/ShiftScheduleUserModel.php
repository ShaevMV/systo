<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Участник планового расписания смены с ролью (PR-A) — зеркало ChangeUserModel,
 * но для плана (shift_schedules), а не факта (changes).
 *
 * role — код из Baza\Shared\Domain\ValueObject\ShiftRole. Сразу в $fillable
 * (иначе через create()/fill() молча не сохранится — урок BAZA.md §6).
 *
 * @property int $id
 * @property int $schedule_id
 * @property int $user_id
 * @property string $role
 */
class ShiftScheduleUserModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'shift_schedule_user';

    protected $fillable = [
        'schedule_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'schedule_id' => 'integer',
        'user_id' => 'integer',
    ];
}
