<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Dto\PushTicketsDto;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class InMemoryMySqlTicketsRepository implements TicketsRepositoryInterface
{
    public function __construct(
        private TicketModel $model,
    )
    {
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function createTickets(TicketDto $ticketDto): bool
    {
        DB::beginTransaction();
        if ($this->model::whereId($ticketDto->getId())->exists()) {
            return true;
        }

        try {
            $this->model::insert($ticketDto->toArray());
            DB::commit();
            return true;
        } catch (Exception $exception) {
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
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param Uuid $orderId
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

    /**
     * Добавить поздзапрос на последний комментарий
     *
     * @return Builder
     */
    private function getSubQueryLastComment(): Builder
    {
        return CommentOrderTicketModel::select('comment')
            ->whereColumn('order_tickets_id', $this->model::TABLE.'.order_ticket_id')
            ->latest()
            ->limit(1)
            ->getQuery();
    }

    public function getTicket(Uuid $ticketId): TicketResponse
    {
        $result = $this->model->where($this->model::TABLE . '.id', '=', $ticketId->value())
            ->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE . '.order_ticket_id', '=', OrderTicketModel::TABLE . '.id')
            ->leftJoin(User::TABLE, OrderTicketModel::TABLE . '.user_id', '=', User::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.id',
                $this->model::TABLE . '.kilter',
                $this->model::TABLE . '.name',
                OrderTicketModel::TABLE . '.phone',
                OrderTicketModel::TABLE . '.status',
                OrderTicketModel::TABLE . '.created_at',
                User::TABLE . '.email',
                User::TABLE . '.city',
            ])->selectSub($this->getSubQueryLastComment(), 'last_comment')
            ->first()?->toArray();

        if (is_null($result)) {
            throw new DomainException("Билет {$ticketId->value()} не найден");
        }

        return new TicketResponse(
            $result['name'],
            $result['kilter'],
            new Uuid($result['id']),
            $result['status'],
            $result['email'],
            $result['phone'],
            $result['city'],
            $result['last_comment'],
            Carbon::parse($result['created_at'])
        );
    }

    /**
     * @throws \Nette\Utils\JsonException
     */
    public function setInBaza(TicketResponse $ticketsDto): bool
    {
        $data = $ticketsDto->toArray();

        if ((!DB::connection('mysqlBaza')->table('el_tickets')
            ->where('uuid', '=', $ticketsDto->getId()->value())->exists())
        ) {
            return DB::connection('mysqlBaza')
                ->table('el_tickets')
                ->insert(
                    $data
                );
        }

        return (DB::connection('mysqlBaza')->table('el_tickets')
                ->where('uuid', '=', $ticketsDto->getId()->value()))->update($data) > 0;
    }


    /**
     * @return Uuid[]
     */
    public function getAllTicketsId(): array
    {
        $rawResult = $this->model::all('id')->toArray();
        $result = [];
        foreach ($rawResult as $item) {
            $result[]=new Uuid($item['id']);
        }

        return $result;
    }
}
