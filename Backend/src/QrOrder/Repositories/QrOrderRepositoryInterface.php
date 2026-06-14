<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use Carbon\Carbon;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;

interface QrOrderRepositoryInterface
{
    public function create(QrOrderDto $dto): bool;

    /** Заказ qr с таким id уже принят (id == id заказа org → идемпотентность приёма). */
    public function existsById(Uuid $id): bool;

    public function findById(Uuid $id): ?QrOrderDto;

    /** Сменить статус принятого заказа (API №2). */
    public function changeStatus(Uuid $id, string $status): bool;

    /** Отметить заказ как выданный (билеты созданы) — защита от повторной выдачи. */
    public function markIssued(Uuid $id, Carbon $issuedAt): bool;
}
