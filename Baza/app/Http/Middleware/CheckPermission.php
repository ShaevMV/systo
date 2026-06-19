<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Auth;
use Baza\Permission\Applications\CanAccess\CanAccess;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC по матрице прав «роль × действие» (Ф2) — приходит на смену бинарному IsAdmin.
 *
 * Роль берётся мягким маппингом ShiftRole::fromUser(is_admin, users.role):
 * is_admin=true → administrator (суперроль, проходит везде) — поэтому текущий
 * вход админов НЕ ломается, пока не появятся не-админские роли.
 *
 * Использование: `->middleware('permission:report.view')`. Перед ним обычно
 * стоит `auth` (гость → редирект на login); здесь — только проверка права.
 * При отказе: API (expectsJson) → JSON 403; web → HTML 403 (фикс UX-бага IsAdmin,
 * который всегда отдавал JSON даже на страницах).
 */
class CheckPermission
{
    public function __construct(
        private CanAccess $canAccess,
    )
    {
    }

    public function handle(Request $request, Closure $next, string $action): Response
    {
        $user = Auth::user();

        if ($user !== null) {
            $role = ShiftRole::fromUser((bool) $user->is_admin, $user->role);

            if ($this->canAccess->check($role, $action)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['errors' => ['error' => 'Forbidden']], 403);
        }

        abort(403);
    }
}
