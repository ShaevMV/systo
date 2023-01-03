<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use Tickets\Order\OrderTicket\Dto\CommentDto;

interface CommentRepositoryInterface
{
    public function addComment(CommentDto $commentDto): bool;
}
