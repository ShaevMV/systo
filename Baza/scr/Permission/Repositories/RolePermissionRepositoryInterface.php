<?php

declare(strict_types=1);

namespace Baza\Permission\Repositories;

interface RolePermissionRepositoryInterface
{
    /**
     * Есть ли у роли право на действие. administrator — суперроль (всегда true).
     */
    public function can(string $role, string $action): bool;

    /**
     * Вся матрица прав: [role => string[] actions]. administrator не включается
     * (суперроль короткозамкнута в коде, прав в таблице не держит).
     *
     * @return array<string, string[]>
     */
    public function getMatrix(): array;

    /**
     * Перезаписать набор прав роли (delete старые → insert новые валидные).
     * administrator игнорируется (его права не редактируются).
     *
     * @param string[] $actions
     */
    public function setMatrix(string $role, array $actions): void;
}
