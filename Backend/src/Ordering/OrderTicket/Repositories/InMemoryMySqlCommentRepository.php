<?php

namespace Tickets\Ordering\OrderTicket\Repositories;

use App\Models\Tickets\Ordering\CommentOrderTicketModel;
use Illuminate\Support\Facades\DB;
use Throwable;
use Tickets\Ordering\OrderTicket\Dto\CommentDto;

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
}
