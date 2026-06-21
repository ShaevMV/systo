<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

interface UserRepositoryInterface
{
    public function createList(array $dataUsers): bool;

    public function clear(): bool;

    public function initAdmin(string $email): bool;

    /**
     * Список персонала для экрана регистрации (Шаг 5). Без секретов.
     *
     * @return array<int, array{id:int, name:string, email:string, is_admin:bool, role:string, role_label:string}>
     */
    public function list(): array;
}
