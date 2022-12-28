<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Tickets\TicketModel;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class InMemoryMySqlTicketsRepository implements TicketsRepositoryInterface
{
    public function __construct(
        private TicketModel $model,
    ) {
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function createTickets(TicketDto $ticketDto): bool
    {
        DB::beginTransaction();
        try {
            $this->model::create($ticketDto->toArray());
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
