<?php

declare(strict_types=1);

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use Baza\Permission\Applications\CanAccess\CanAccess;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Redirect;

/**
 * Редактор матрицы прав «роль × действие» через интерфейс (Ф2 PR-6).
 *
 * Доступ — только право rbac.manage (по дефолту лишь administrator). Сам
 * administrator — суперроль: в матрице показан задизейбленным/всегда отмеченным
 * и не редактируется (репозиторий игнорирует setMatrix для него). БД — в репозитории
 * (через CanAccess → RolePermissionRepository), контроллер её не трогает.
 */
class PermissionController extends Controller
{
    public function __construct(
        private CanAccess $canAccess,
    )
    {
    }

    public function index(): View
    {
        return view('permission.matrix', [
            'roles'     => ShiftRole::catalog(),
            'actions'   => ShiftPermission::catalog(),
            'matrix'    => $this->canAccess->getMatrix(),
            'adminRole' => ShiftRole::ADMINISTRATOR,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        /** @var array<string, string[]> $perm */
        $perm = (array) $request->input('perm', []);

        // Форма — источник правды: для каждой редактируемой роли пишем отмеченный
        // набор (неотмеченное не приходит → право снимается). administrator пропускаем
        // (суперроль, чекбоксы задизейблены и в POST не попадают).
        foreach (ShiftRole::all() as $role) {
            if ($role === ShiftRole::ADMINISTRATOR) {
                continue;
            }

            $this->canAccess->setMatrix($role, (array) ($perm[$role] ?? []));
        }

        return Redirect::route('permission.index')->with('status', 'Права доступа обновлены');
    }
}
