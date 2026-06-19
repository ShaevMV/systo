<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Строка матрицы прав «роль × действие» (Ф2).
 *
 * Наличие строки = право есть. role — код ShiftRole, action — код ShiftPermission.
 *
 * @property int $id
 * @property string $role
 * @property string $action
 */
class BazaRolePermissionModel extends Model
{
    protected $table = self::TABLE;

    public const TABLE = 'baza_role_permissions';

    protected $fillable = [
        'role',
        'action',
    ];
}
