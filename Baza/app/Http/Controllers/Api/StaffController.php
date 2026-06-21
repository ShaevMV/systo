<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Baza\Shared\Domain\ValueObject\ShiftRole;
use Baza\Tickets\Repositories\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Регистрация персонала из нового PWA (Шаг 5). Доступ — право staff.manage
 * (по дефолту только administrator). Обёртка над идемпотентным createList
 * (updateOrCreate по email, is_admin/role вне fillable → ставит репозиторий). БД только в репо.
 */
class StaffController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'staff' => $this->users->list(),
            'roles' => ShiftRole::catalog(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'is_admin' => 'sometimes|boolean',
            'role' => ['nullable', Rule::in(ShiftRole::all())],
        ]);

        // Идемпотентно: повтор по тому же email обновит (перевыпуск пароля/смена роли), без дубля.
        $this->users->createList([[
            'email' => $data['email'],
            'name' => $data['name'],
            'password' => $data['password'],
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'role' => $data['role'] ?? null,
        ]]);

        return response()->json(['success' => true, 'message' => 'Сотрудник сохранён']);
    }
}
