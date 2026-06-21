<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\ListComments;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\CommentResponse;

class GetOrderCommentQueryHandler implements QueryHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function __invoke(GetOrderCommentQuery $query): ?CommentResponse
    {
        $comment = $this->commentRepository->findById($query->id);

        return $comment === null ? null : new CommentResponse($comment);
    }
}
