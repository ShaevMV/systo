<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use App\Models\Tickets\Ordering\CommentOrderTicketModel;
use App\Models\Tickets\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Tickets\Ordering\OrderTicketModel;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;
use Tickets\Ordering\OrderTicket\Domain\OrderTicketItem;
use Tickets\Ordering\OrderTicket\Dto\OrderTicketDto;
use Tickets\Shared\Domain\Criteria\Filter;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlOrderTicketRepository implements OrderTicketRepositoryInterface
{
    public function __construct(
        private OrderTicketModel $model,
    ) {
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
     * @throws JsonException
     */
    public function getUserList(Uuid $userId): array
    {
        $lastComment = CommentOrderTicketModel::select('comment')
            ->whereColumn('order_tickets_id', $this->model::TABLE.'.id')
            ->latest()
            ->limit(1)
            ->getQuery();

        $rawData = $this->model::whereUserId($userId->value())
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE.'.ticket_type_id',
                '=',
                TicketTypesModel::TABLE.'.id')
            ->select([
                $this->model::TABLE.'.*',
                TicketTypesModel::TABLE.'.name',
            ])
            ->selectSub($lastComment, 'last_comment')
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = OrderTicketItem::fromState($datum);
        }

        return $result;
    }

    /**
     * @throws JsonException
     */
    public function findOrder(Uuid $uuid): ?OrderTicketItem
    {
        $rawData = $this->model::whereId($uuid->value())
            ->with('comments')
            ->with('ticketType')
            ->with('typeOfPayment')
            ->first()
            ?->toArray();

        return $rawData !== null ? OrderTicketItem::fromItemOrderState($rawData) : null;
    }

    /**
     * @throws JsonException
     */
    public function getList(Filters $filters): array
    {
        $builder = $this->model->leftJoin(
            User::TABLE, $this->model::TABLE.'.user_id',
            '=',
            User::TABLE.'.id');

        /** @var Filter $filter */
        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->value()->value()
                );
            }
        }
        $rawData = $builder->get()
            ->toArray();

        $result = [];

        foreach ($rawData as $datum) {
            $result[] = OrderTicketItem::fromState($datum);
        }

        return $result;
    }
}
