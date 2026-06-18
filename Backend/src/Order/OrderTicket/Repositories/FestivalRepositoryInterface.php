<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Shared\Domain\ValueObject\Uuid;

interface FestivalRepositoryInterface
{
    public function create(FestivalDto $dto): bool;

    public function get(Uuid $id): FestivalDto;

    /**
     * @return FestivalDto[]
     */
    public function getFestivalByTicketTypeId(Uuid $ticketTypeId): array;

    /**
     * @return FestivalDto[]
     */
    public function getFestivalList(): array;

    /**
     * Список фестивалей с фильтрами/сортировкой (для админ-CRUD).
     *
     * @return Collection<int, FestivalDto>
     */
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function editItem(Uuid $id, FestivalDto $data): bool;

    public function remove(Uuid $id): bool;
}
