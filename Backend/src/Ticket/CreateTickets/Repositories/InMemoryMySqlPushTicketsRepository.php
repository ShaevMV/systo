<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Nette\Utils\JsonException;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\PushTicket\Get\PushTicketsResponse;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;

class InMemoryMySqlPushTicketsRepository implements PushTicketsRepositoryInterface
{
    public function __construct(
        private TicketModel $model,
    )
    {
    }

    private function getRequest(): Builder|TicketModel
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

    /**
     * @throws Exception
     */
    public function getTicket(Uuid $ticketId): PushTicketsDto
    {
        $rawData = $this->getRequest()
            ->where($this->model::TABLE . '.id', '=', $ticketId->value())
            ->first()
            ?->toArray();
        if (is_null($rawData)) {
            throw new Exception('Не получилось найти билет ' . $ticketId->value());
        }
        return PushTicketsDto::fromState($rawData);
    }


    /**
     * @param Uuid|null $uuid
     * @return PushTicketsDto[]
     * @throws Exception
     */
    public function getTicketsAllOrFirst(?Uuid $uuid): array
    {
        if (!is_null($uuid)) {
            return [$this->getTicket($uuid)];
        }

        $rawData = $this->getRequest()
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = PushTicketsDto::fromState($datum);
        }

        return $result;
    }

    /**
     * @throws JsonException
     */
    public function setInBaza(PushTicketsDto $ticketsDto): bool
    {
        $data = $ticketsDto->toArray();

        if (!$rawModel = DB::connection('mysqlBaza')->table('el_tickets')
            ->where('uuid', '=', $ticketsDto->getUuid()->value())) {
            return DB::connection('mysqlBaza')
                ->table('el_tickets')
                ->insert(
                    $data
                );
        }
        return $rawModel->update($data) > 0;
    }
}
