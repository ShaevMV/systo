<?php

declare(strict_types=1);

namespace Tickets\TypesOfPayment\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TypesOfPayment\Dto\TypesOfPaymentDto;

interface TypesOfPaymentRepositoryInterface
{
    public function getList(Filters $filters): Collection;
    public function getItem(Uuid $id): TypesOfPaymentDto;
    public function editItem(Uuid $id, TypesOfPaymentDto $paymentDto): bool;
    public function create(TypesOfPaymentDto $paymentDto): bool;
    public function remove(Uuid $id): bool;
}
