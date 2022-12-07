<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Repositories;

use Tickets\Ordering\OrderTicket\Dto\CommentDto;

interface CommentRepositoryInterface
{
    public function addComment(CommentDto $commentDto): bool;
}
