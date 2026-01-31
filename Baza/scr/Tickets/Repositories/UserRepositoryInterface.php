<?php

declare(strict_types=1);

namespace Baza\Tickets\Repositories;

interface UserRepositoryInterface
{
    public function createList(array $dataUsers): bool;

    public function clear(): bool;

    public function initAdmin(string $email): bool;
}
