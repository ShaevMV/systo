<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\QrOrder\Dto\QrOrderDto;

interface QrOrderRepositoryInterface
{
    public function create(QrOrderDto $dto): bool;

    /** Заказ qr с таким id уже принят (id == id заказа org → идемпотентность приёма). */
    public function existsById(Uuid $id): bool;

    public function findById(Uuid $id): ?QrOrderDto;
}
