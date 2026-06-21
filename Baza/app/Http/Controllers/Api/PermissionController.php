<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Permission\Applications\CanAccess\CanAccess;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON-редактор матрицы прав «роль × действие» для PWA (Шаг 4): /api/permissions/matrix.
 *
 * Перенос старого Blade /permissions в новый интерфейс. Логику НЕ переписываем — тот же
 * CanAccess (getMatrix/setMatrix), БД только в репозитории. Доступ — право rbac.manage
 * (middleware на роутах). administrator — суперроль: в матрице задизейблен и не редактируется.
 */
class PermissionController extends Controller
{
    public function __construct(
        private readonly CanAccess $canAccess,
    ) {}

    public function matrix(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'roles' => ShiftRole::catalog(),
            'actions' => ShiftPermission::catalog(),
            'matrix' => $this->canAccess->getMatrix(),
            'admin_role' => ShiftRole::ADMINISTRATOR,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        /** @var array<string, string[]> $perm */
        $perm = (array) $request->input('perm', []);

        // Форма = источник правды: для каждой редактируемой роли пишем отмеченный набор
        // (неотмеченное не пришло → право снимается). administrator пропускаем (суперроль).
        foreach (ShiftRole::all() as $role) {
            if ($role === ShiftRole::ADMINISTRATOR) {
                continue;
            }
            $this->canAccess->setMatrix($role, (array) ($perm[$role] ?? []));
        }

        return response()->json([
            'success' => true,
            'matrix' => $this->canAccess->getMatrix(),
            'message' => 'Права доступа обновлены',
        ]);
    }
}
