<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| ОБРАЗЕЦ списка персонала КПП (committed — это шаблон, НЕ боевые данные)
|--------------------------------------------------------------------------
|
| Перед фестивалем:
|   1. Скопируй этот файл рядом как  staff_users.php  (он в .gitignore).
|   2. Впиши реальные логины/пароли сотрудников.
|   3. Заведи их:  php artisan tickets:crateUser
|      (или:       php artisan db:seed --class=Database\\Seeders\\StaffUsersSeeder)
|
| Идемпотентно: повторный запуск ОБНОВИТ пароли существующих email (по email),
| не создаст дублей. Пароли здесь — открытым текстом, при заведении хешируются.
|
| Формат строки:  [email, имя, пароль, is_admin(bool), role(опц.)]
|   role — код роли смены (ShiftRole): administrator | shift_chief | ticketer |
|   kpp_commandant | guard. Можно НЕ указывать — тогда роль выведется по is_admin
|   (true → administrator, false → ticketer). Невалидная роль игнорируется.
|
| ВАЖНО (безопасность): staff_users.php НЕ коммитить. Пароли, попавшие в git,
| считаются скомпрометированными — перед фестивалем выдавай НОВЫЕ.
|
*/

return [
    // [email,                     имя,        пароль,        is_admin, role(опц.)]
    ['admin@example.test',         'Админ',     'CHANGE_ME',   true,     'administrator'],
    ['chief@example.test',         'Начсмены',  'CHANGE_ME',   false,    'shift_chief'],
    ['security1@example.test',     'Охрана 1',  'CHANGE_ME',   false,    'guard'],
    ['ticketer1@example.test',     'Билетёр 1', 'CHANGE_ME',   false], // role опущена → ticketer
];
