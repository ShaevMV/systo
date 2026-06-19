<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Baza\Tickets\Repositories\UserRepositoryInterface;
use Illuminate\Console\Command;

/**
 * Заведение персонала КПП на фестиваль.
 *
 * Раньше список логинов/паролей был зашит прямо здесь (40 паролей открытым
 * текстом в git). Теперь список читается из НЕкоммитимого файла
 * config('baza.staff_users_path') (в репо — staff_users.example.php).
 * Пароли в файле открытым текстом → хешируются в репозитории.
 * Идемпотентно по email: повторный запуск = перевыпуск паролей, без дублей.
 */
class CreateUserCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'tickets:crateUser';

    /**
     * @var string
     */
    protected $description = 'Завести персонал КПП из gitignored-файла (config baza.staff_users_path): пароли хешируются, идемпотентно по email';

    public function handle(UserRepositoryInterface $repository): int
    {
        $path = config('baza.staff_users_path');

        if (! is_string($path) || ! is_file($path)) {
            $this->error(
                'Нет файла со списком персонала: '.$path.PHP_EOL
                .'Скопируй database/seeders/data/staff_users.example.php → staff_users.php '
                .'и впиши логины/пароли.'
            );

            return Command::FAILURE;
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

        $this->info('Заведено/обновлено сотрудников: '.count($users));

        return Command::SUCCESS;
    }
}
