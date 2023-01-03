<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\ValueObject;

use Carbon\Carbon;
use JsonException;
use Tickets\Shared\Domain\Entity\EntityDataInterface;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class CommentForOrder implements EntityDataInterface
{
    public function __construct(
        public Uuid $id,
        public Uuid $user_id,
        public string $comment,
        public bool $is_checkin,
        public Carbon $created_at,
    ) {
    }


    public function __toString(): string
    {
        return $this->comment;
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode([
            'id' => $this->id->value(),
            'user_id' => $this->user_id->value(),
            'comment' => $this->comment,
            'is_checkin' => $this->is_checkin,
            'created_at' => (string)$this->created_at
        ], JSON_THROW_ON_ERROR);
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            new Uuid($data['user_id']),
            $data['comment'],
            (bool)$data['is_checkin'],
            new Carbon($data['created_at'])
        );
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function isIsCheckin(): bool
    {
        return $this->is_checkin;
    }

}
