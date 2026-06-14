<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application;

use Carbon\Carbon;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;

/**
 * Приём заказов от витрины qr (API №1) + смена статуса с выдачей билетов (API №2).
 * Тонкий слой над репозиторием (БД — только в репозитории, правило №1).
 */
final class QrOrderApplication
{
    /** Статусы контракта qr, означающие «оплачено» → запускают выдачу билетов. */
    private const PAID_STATUSES = ['оплачен', 'paid'];

    public function __construct(
        private readonly QrOrderRepositoryInterface $repository,
        private readonly QrOrderIssuer $issuer,
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
     * Сменить статус принятого заказа (API №2). Возвращает false, если заказа нет.
     *
     * При переходе в «оплачен» запускается выдача билетов (PDF/письма) — один раз
     * (защита по issued_at: повторный «оплачен» не выдаёт билеты снова).
     */
    public function changeStatus(Uuid $id, string $status): bool
    {
        $order = $this->repository->findById($id);
        if ($order === null) {
            return false;
        }

        $this->repository->changeStatus($id, $status);

        if ($this->isPaid($status) && $order->getIssuedAt() === null) {
            // Перечитываем заказ с новым статусом для выдачи.
            $this->issuer->issue($this->repository->findById($id) ?? $order);
            $this->repository->markIssued($id, Carbon::now());
        }

        return true;
    }

    private function isPaid(string $status): bool
    {
        return in_array(mb_strtolower(trim($status)), self::PAID_STATUSES, true);
    }
}
