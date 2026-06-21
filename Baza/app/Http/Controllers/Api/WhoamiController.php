<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\ValueObject\ShiftPermission;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Illuminate\Http\JsonResponse;

/**
 * «Кто я» для PWA (Шаг 3): GET /api/whoami.
 *
 * Сессионная auth персонала (web-группа). Отдаёт фронту роль + список прав текущего
 * сотрудника — чтобы PWA гейтил пункты меню (Права/Смены/Регистрация) и понимал, видит ли
 * полную карточку (ticket.pii). НЕ для безопасности (фильтрация ПДн — на бэкенде), а для UI.
 */
class WhoamiController extends Controller
{
    public function __construct(
        private readonly RolePermissionRepositoryInterface $rolePermissions,
    ) {}

    public function index(): JsonResponse
    {
        $user = \Auth::user();
        $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

        $permissions = [];
        foreach (ShiftPermission::all() as $action) {
            if ($this->rolePermissions->can($role, $action)) {
                $permissions[] = $action;
            }
        }

        return response()->json([
            'success' => true,
            'id' => $user->id,
            'name' => $user->name ?? ($user->login ?? null),
            'email' => $user->email ?? null,
            'is_admin' => (bool) $user->is_admin,
            'role' => $role,
            'role_label' => ShiftRole::label($role),
            'permissions' => $permissions,
            'can_view_pii' => in_array(ShiftPermission::TICKET_PII, $permissions, true),
        ]);
    }
}
