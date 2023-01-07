<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Tickets\TicketModel;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
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
            $this->model::insert($ticketDto->toArray());
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteTicketsByOrderId(Uuid $orderId): bool
    {
        DB::beginTransaction();
        try {
            $this->model::whereOrderTicketId($orderId->value())->delete();
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param  Uuid  $orderId
     * @return Uuid[]
     */
    public function getListIdByOrderId(Uuid $orderId): array
    {
        $result = [];

        $listIds = $this->model::whereOrderTicketId($orderId->value())
            ->get()
            ->toArray();

        foreach ($listIds as $id) {
            $result[] = new Uuid($id['id']);
        }

        return $result;
    }
}
