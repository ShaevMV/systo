<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\ValueObject;

use Carbon\Carbon;
use JsonException;
use Shared\Domain\Entity\EntityDataInterface;
use Shared\Domain\ValueObject\Uuid;

final class CommentForOrder implements EntityDataInterface
{
    public function __construct(
        public Uuid $id,
        public ?Uuid $user_id,
        public string $comment,
        public bool $is_checkin,
        public Carbon $created_at,
        public ?string $author_name = null,
        public string $author_source = CommentSource::ORG_USER,
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
            'user_id' => $this->user_id?->value(),
            'comment' => $this->comment,
            'is_checkin' => $this->is_checkin,
            'created_at' => (string)$this->created_at,
            'author_name' => $this->author_name,
            'author_source' => $this->author_source,
        ], JSON_THROW_ON_ERROR);
    }

    public static function fromState(array $data): self
    {
        return new self(
            new Uuid($data['id']),
            isset($data['user_id']) && $data['user_id'] !== null ? new Uuid($data['user_id']) : null,
            $data['comment'],
            (bool)$data['is_checkin'],
            new Carbon($data['created_at']),
            $data['author_name'] ?? null,
            $data['author_source'] ?? CommentSource::ORG_USER,
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

    public function getAuthorName(): ?string
    {
        return $this->author_name;
    }

    public function getAuthorSource(): string
    {
        return $this->author_source;
    }
}
