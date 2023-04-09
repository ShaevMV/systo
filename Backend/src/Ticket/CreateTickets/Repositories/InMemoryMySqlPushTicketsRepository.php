<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use DomainException;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Application\PushTicket\PushTicketsResponse;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class InMemoryMySqlPushTicketsRepository implements PushTicketsRepositoryInterface
{
    public function __construct(
        private TicketModel $model,
    )
    {
    }

    private function getRequest(): TicketModel
    {
        return $this->model::withTrashed()
            ->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE . '.order_ticket_id', '=', OrderTicketModel::TABLE . '.id')
            ->leftJoin(User::TABLE, OrderTicketModel::TABLE . '.user_id', '=', User::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.kilter',
                $this->model::TABLE . '.id',
                $this->model::TABLE . '.name',
                OrderTicketModel::TABLE . '.phone',
                OrderTicketModel::TABLE . '.status',
                User::TABLE . '.email',
                OrderTicketModel::TABLE . '.created_at',
            ]);
    }

    public function getTicket(Uuid $ticketId): PushTicketsResponse
    {
        $rawData = $this->getRequest()
            ->whereId($ticketId->value())
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = PushTicketsDto::fromState($datum);
        }

        return new PushTicketsResponse($result);
    }

    public function getAllTickets(): PushTicketsResponse
    {
        $rawData = $this->getRequest()
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = PushTicketsDto::fromState($datum);
        }

        return new PushTicketsResponse($result);
    }
}
