<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Shared\Domain\Bus\Command\Command;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\ValueObject\CommentSource;

/**
 * Обобщённое добавление комментария в тред заказа (append-only).
 *
 * Источник записи (author_source) + опциональные автор-org (user_id) и отображаемое имя (author_name).
 * Старый difficulties/live-issued флоу использует этот же путь с author_source = org_user.
 */
final class AddOrderCommentCommand implements Command
{
    public function __construct(
        private Uuid $id,
        private Uuid $orderId,
        private string $message,
        private string $authorSource = CommentSource::ORG_USER,
        private ?Uuid $userId = null,
        private ?string $authorName = null,
        private bool $isCheckin = false,
    ) {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getAuthorSource(): string
    {
        return $this->authorSource;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function isCheckin(): bool
    {
        return $this->isCheckin;
    }
}
