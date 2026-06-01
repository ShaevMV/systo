<?php

declare(strict_types=1);

namespace Tickets\Option\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Option\Dto\OptionDto;
use Tickets\Option\Dto\OptionForTicketTypeView;
use Tickets\Option\Dto\OptionTicketTypeBindingDto;

interface OptionRepositoryInterface
{
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): OptionDto;

    public function create(OptionDto $data): bool;

    public function editItem(Uuid $id, OptionDto $data): bool;

    public function remove(Uuid $id): bool;

    /**
     * Привязать опцию к списку типов билетов (many-to-many) с описанием на каждой связке.
     * Полная синхронизация: старые привязки удаляются, остаются только из массива.
     *
     * @param  OptionTicketTypeBindingDto[]  $bindings
     */
    public function syncTicketTypes(Uuid $optionId, array $bindings): void;

    /**
     * Получить привязки опции к типам билетов (вместе с описаниями).
     *
     * @return OptionTicketTypeBindingDto[]
     */
    public function getTicketTypeBindings(Uuid $optionId): array;

    /**
     * Получить активные опции для конкретного типа билета с актуальной ценой
     * и описанием специфичным для этого типа билета (read-модель для фронта).
     *
     * @return OptionForTicketTypeView[]
     */
    public function getActiveOptionsForTicketType(Uuid $ticketTypeId): array;
}
