<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto;

use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\Entity\EntityInterface;
use Shared\Domain\ValueObject\Uuid;

final class CommentDto extends AbstractionEntity
{
    public function __construct(
        protected Uuid $user_id,
        protected Uuid $order_tickets_id,
        protected string $comment,
    ) {
    }


    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['user_id']),
            new Uuid($data['order_id']),
            $data['comment'],
        );
    }
}
