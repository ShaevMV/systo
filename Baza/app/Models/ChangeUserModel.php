<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Участник смены с ролью (Ф2) — строка на каждого участника смены.
 *
 * role — код из Baza\Shared\Domain\ValueObject\ShiftRole. Сразу в $fillable
 * (иначе через create()/fill() молча не сохранится — урок count_auto_tickets/
 * is_admin, см. BAZA.md §6).
 *
 * @property int $id
 * @property int $change_id
 * @property int $user_id
 * @property string $role
 */
class ChangeUserModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'change_user';

    protected $fillable = [
        'change_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'change_id' => 'integer',
        'user_id' => 'integer',
    ];
}
