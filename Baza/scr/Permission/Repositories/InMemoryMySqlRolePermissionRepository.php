<?php

declare(strict_types=1);

namespace Baza\Permission\Repositories;

use App\Models\BazaRolePermissionModel;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use DB;

class InMemoryMySqlRolePermissionRepository implements RolePermissionRepositoryInterface
{
    public function can(string $role, string $action): bool
    {
        // administrator — суперроль: всегда true, в таблице прав не держится
        // (защита «нельзя закрыть себе доступ через матрицу»).
        if ($role === ShiftRole::ADMINISTRATOR) {
            return true;
        }

        return BazaRolePermissionModel::where('role', $role)
            ->where('action', $action)
            ->exists();
    }

    public function getMatrix(): array
    {
        $matrix = [];

        foreach (BazaRolePermissionModel::all(['role', 'action']) as $row) {
            $matrix[$row->role][] = $row->action;
        }

        return $matrix;
    }

    public function setMatrix(string $role, array $actions): void
    {
        // administrator не редактируется из UI (суперроль короткозамкнута в can()).
        if ($role === ShiftRole::ADMINISTRATOR) {
            return;
        }

        DB::transaction(function () use ($role, $actions) {
            BazaRolePermissionModel::where('role', $role)->delete();

            foreach (array_unique($actions) as $action) {
                // только валидные коды действий (защита от мусора из формы)
                if (ShiftPermission::isValid((string) $action)) {
                    BazaRolePermissionModel::create([
                        'role'   => $role,
                        'action' => (string) $action,
                    ]);
                }
            }
        });
    }
}
