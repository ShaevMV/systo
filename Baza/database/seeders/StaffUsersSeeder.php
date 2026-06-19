<?php

declare(strict_types=1);

namespace Database\Seeders;

use Baza\Tickets\Repositories\UserRepositoryInterface;
use Illuminate\Database\Seeder;

/**
 * Заведение персонала КПП на фестиваль из НЕкоммитимого файла-списка
 * (config('baza.staff_users_path'), формат — staff_users.example.php).
 *
 * Альтернатива команде tickets:crateUser для тех, кто заводит через db:seed:
 *   php artisan db:seed --class=Database\Seeders\StaffUsersSeeder
 *
 * НЕ подключён к DatabaseSeeder намеренно: db:seed (dev/staging bootstrap)
 * сеет тестовые фикстуры (UsersTableSeeder с админом id=1), а боевой персонал
 * заводится отдельным шагом владельцем перед фестивалем. Идемпотентно по email
 * (повторный запуск = перевыпуск паролей, без дублей). Пароли хешируются в
 * репозитории (БД-доступ только там — Dependency Rule).
 */
class StaffUsersSeeder extends Seeder
{
    public function run(UserRepositoryInterface $repository): void
    {
        $path = config('baza.staff_users_path');

        if (! is_string($path) || ! is_file($path)) {
            $this->command?->warn(
                'StaffUsersSeeder: нет файла '.$path
                .' — скопируй staff_users.example.php в staff_users.php и впиши логины/пароли.'
            );

            return;
        }

        /** @var array<int, array{0:string, 1:string, 2:string, 3?:bool, 4?:string}> $rows */
        $rows = require $path;

        $users = array_map(
            static fn (array $row): array => [
                'email'    => $row[0],
                'name'     => $row[1],
                'password' => $row[2],
                'is_admin' => (bool) ($row[3] ?? false),
                'role'     => $row[4] ?? null, // опц. роль смены (ShiftRole); невалидную репозиторий игнорирует
            ],
            $rows
        );

        $repository->createList($users);
    }
}
