<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use Shared\Domain\ValueObject\Uuid;
use Tickets\InviteLink\Domain\InviteLink;
use Tickets\InviteLink\Responses\InviteLinkResponse;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderStatusListDto;

interface InviteLinkRepositoryInterface
{
    public function isPaidOrderByUserId(Uuid $userId): bool;

    public function addOrderInInviteLink(Uuid $id, Uuid $orderId): void;

    public function getOrderStatusListInInviteLink(Uuid $id): OrderStatusListDto;
}
