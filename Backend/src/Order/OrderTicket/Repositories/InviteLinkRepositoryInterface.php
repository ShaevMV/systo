<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\InviteLink\Domain\InviteLink;
use Tickets\InviteLink\Responses\InviteLinkResponse;

interface InviteLinkRepositoryInterface
{
    public function isPaidOrderByUserId(Uuid $userId): bool;
}
