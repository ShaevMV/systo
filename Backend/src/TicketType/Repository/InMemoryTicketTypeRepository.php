<?php

declare(strict_types=1);

namespace Tickets\TicketType\Repository;

use App\Models\Festival\TicketTypesModel;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TicketType\Dto\TicketTypeDto;
use Tickets\TicketType\Response\TicketTypeGetListResponse;

class InMemoryTicketTypeRepository implements TicketTypeRepositoryInterface
{
    public function __construct(
        private TicketTypesModel $model,
    )
    {
    }


    public function getList(Filters $filters): Collection
    {
        return FilterBuilder::build($this->model,$filters)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->each(fn(TicketTypesModel $model) => TicketTypeDto::fromState($model->toArray()));
    }

    public function getItem(Uuid $id): TicketTypeDto
    {
        // TODO: Implement getItem() method.
    }

    public function editItem(Uuid $id, TicketTypeDto $paymentDto): bool
    {
        // TODO: Implement editItem() method.
    }

    public function create(TicketTypeDto $paymentDto): bool
    {
        // TODO: Implement create() method.
    }

    public function remove(Uuid $id): bool
    {
        // TODO: Implement remove() method.
    }
}
