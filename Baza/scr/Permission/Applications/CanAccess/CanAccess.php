<?php

declare(strict_types=1);

namespace Baza\Permission\Applications\CanAccess;

use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

/**
 * Проверка прав роли (Ф2) — точка enforcement для middleware/меню.
 * Bus-паттерн как GetCurrentChanges. administrator — суперроль (всегда true).
 */
class CanAccess
{
    private QueryBus $bus;

    public function __construct(
        CanAccessQueryHandler $canAccessQueryHandler,
        private RolePermissionRepositoryInterface $repository,
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            CanAccessQuery::class => $canAccessQueryHandler,
        ]);
    }

    public function check(string $role, string $action): bool
    {
        /** @var CanAccessResponse $result */
        $result = $this->bus->ask(new CanAccessQuery($role, $action));

        return $result->isAllowed();
    }

    /**
     * Текущая матрица прав (для экрана редактора, PR-6).
     *
     * @return array<string, string[]>
     */
    public function getMatrix(): array
    {
        return $this->repository->getMatrix();
    }

    /**
     * Перезаписать права роли (из экрана редактора, PR-6).
     *
     * @param string[] $actions
     */
    public function setMatrix(string $role, array $actions): void
    {
        $this->repository->setMatrix($role, $actions);
    }
}
