<?php

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Tickets\Order\OrderTicket\Dto\CommentDto;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Shared\Domain\Bus\Command\CommandHandler;

class AddCommentCommandHandler implements CommandHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository
    ){
    }

    public function __invoke(AddCommentCommand $addCommentCommand): void
    {
        $this->commentRepository->addComment(new CommentDto(
            $addCommentCommand->getUserId(),
            $addCommentCommand->getOrderId(),
            $addCommentCommand->getMessage(),
        ));
    }
}
