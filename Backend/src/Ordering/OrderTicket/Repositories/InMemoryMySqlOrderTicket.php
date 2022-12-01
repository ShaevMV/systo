<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use App\Models\Tickets\Ordering\CommentOrderTicket;
use App\Models\Tickets\Ordering\InfoForOrder\Models\TicketTypes;
use App\Models\Tickets\Ordering\OrderTicket;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlOrderTicket implements OrderTicketInterface
{
    public function __construct(
        private OrderTicket $model,
    ){
    }


    /**
     * @throws Throwable
     */
    public function create(OrderTicketDto $orderTicketDto): bool
    {
        DB::beginTransaction();
        try {
            $this->model::create($orderTicketDto->toArray());
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param  Uuid  $userId
     *
     * @return OrderTicketDto[]
     *
     * @throws \JsonException
     */
    public function getUserList(Uuid $userId): array
    {
        $result = [];
        $lastComment = CommentOrderTicket::select('comment')
            ->whereColumn('order_tickets_id', $this->model::TABLE.'.id')
            ->latest()
            ->limit(1)
            ->getQuery();

        $rawData = $this->model::whereUserId($userId->value())
            ->leftJoin(TicketTypes::TABLE, $this->model::TABLE.'.ticket_type_id','=',TicketTypes::TABLE.'.id')
            ->select([
                $this->model::TABLE.'.*',
                TicketTypes::TABLE.'.name',
            ])
            ->selectSub($lastComment, 'last_comment')
            ->get()
            ->toArray();
        foreach ($rawData as $datum) {
            $result[] = OrderTicketItem::fromState($datum);
        }

        return $result;
    }
}
