<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Ordering\InviteLinkModel;
use App\Models\Ordering\OrderTicketModel;
use DomainException;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\InviteLink\Domain\InviteLink;
use Tickets\InviteLink\Responses\InviteLinkResponse;

class InMemoryInviteLinkRepository implements InviteLinkRepositoryInterface
{
    public function __construct(
        private OrderTicketModel $orderTicketModel,
    )
    {
    }

    public function isPaidOrderByUserId(Uuid $userId): bool
    {
        return $this->orderTicketModel::where([
            'user_id' => $userId->value(),
            'status' => Status::PAID,
        ])->exists();
    }
}
