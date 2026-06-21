<?php

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Ordering\CommentOrderTicketModel;
use Illuminate\Support\Facades\DB;
use Throwable;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\CommentDto;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;

class InMemoryMySqlCommentRepository implements CommentRepositoryInterface
{
    public function __construct(
        private CommentOrderTicketModel $model
    ){
    }


    /**
     * @throws Throwable
     */
    public function addComment(CommentDto $commentDto): bool
    {
        DB::beginTransaction();
        try {

            $this->model::create($commentDto->toArray());
            DB::commit();
            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Весь тред комментариев заказа в хронологическом порядке (created_at ASC).
     *
     * @return CommentForOrder[]
     */
    public function listByOrder(Uuid $orderId): array
    {
        return $this->model::query()
            ->whereOrderTicketsId($orderId->value())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(static fn (CommentOrderTicketModel $row): CommentForOrder => CommentForOrder::fromState($row->toArray()))
            ->all();
    }

    public function findById(Uuid $id): ?CommentForOrder
    {
        $row = $this->model::query()->whereId($id->value())->first();

        return $row === null ? null : CommentForOrder::fromState($row->toArray());
    }
}
