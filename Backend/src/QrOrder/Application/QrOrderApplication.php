<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application;

use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;

/**
 * Приём заказов от витрины qr (API №1) + чтение принятого заказа.
 * Тонкий слой над репозиторием (БД — только в репозитории, правило №1).
 */
final class QrOrderApplication
{
    public function __construct(
        private readonly QrOrderRepositoryInterface $repository,
    ) {
    }

    /**
     * Принять заказ. Идемпотентно: повторный приём заказа с тем же id (== id заказа qr/org)
     * не создаёт дубль — возвращает true без повторной записи.
     */
    public function create(QrOrderDto $dto): bool
    {
        if ($this->repository->existsById($dto->getId())) {
            return true;
        }

        return $this->repository->create($dto);
    }

    public function getItem(Uuid $id): ?QrOrderDto
    {
        return $this->repository->findById($id);
    }

    /**
     * Сменить статус принятого заказа (API №2, шаг 2a).
     * Возвращает false, если заказа с таким id нет.
     *
     * TODO(2b): при переходе в «оплачен»/выдать — запустить выдачу билетов (PDF/письма).
     */
    public function changeStatus(Uuid $id, string $status): bool
    {
        if (! $this->repository->existsById($id)) {
            return false;
        }

        return $this->repository->changeStatus($id, $status);
    }
}
