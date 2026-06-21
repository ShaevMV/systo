<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ListComments;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\CommentThreadResponse;

class ListOrderCommentsQueryHandler implements QueryHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function __invoke(ListOrderCommentsQuery $query): CommentThreadResponse
    {
        return new CommentThreadResponse(
            $this->commentRepository->listByOrder($query->orderId)
        );
    }
}
