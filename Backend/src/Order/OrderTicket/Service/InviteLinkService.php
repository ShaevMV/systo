<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Service;

use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Repositories\InviteLinkRepositoryInterface;

class InviteLinkService
{
    private const LINK_PATCH = 'https://org.spaceofjoy.ru/invite/';

    public function __construct(
        private InviteLinkRepositoryInterface $repository
    )
    {
    }

    public function getLink(Uuid $userId): ?string
    {
        return $this->repository->isPaidOrderByUserId($userId) ? self::LINK_PATCH . $userId->value() : null;
    }

    public function isPaidOrderByUserId(Uuid $userId): bool
    {
        return $this->repository->isPaidOrderByUserId($userId);
    }
}
