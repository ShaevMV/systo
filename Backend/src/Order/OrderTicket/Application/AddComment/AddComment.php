<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Throwable;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Tickets\Order\OrderTicket\ValueObject\CommentSource;

/**
 * Тонкий слой добавления комментария в тред заказа (как Location/QrOrder).
 *
 * `send()` сохранён обратносовместимым для difficulties/live-issued флоу
 * (ChangeStatusCommandHandler) — это org-юзер. Для прочих источников — `add()`.
 */
final class AddComment
{
    private InMemorySymfonyCommandBus $commandBus;

    public function __construct(AddOrderCommentCommandHandler $handler)
    {
        $this->commandBus = new InMemorySymfonyCommandBus([
            AddOrderCommentCommand::class => $handler,
        ]);
    }

    /**
     * Обратносовместимый путь: комментарий от org-юзера (admin/manager).
     *
     * @throws Throwable
     */
    public function send(Uuid $orderId, Uuid $userId, string $message): void
    {
        $this->add($orderId, $message, CommentSource::ORG_USER, $userId);
    }

    /**
     * Обобщённое добавление комментария в тред (любой источник).
     * Возвращает id созданной записи треда.
     *
     * @throws Throwable
     */
    public function add(
        Uuid $orderId,
        string $message,
        string $authorSource = CommentSource::ORG_USER,
        ?Uuid $userId = null,
        ?string $authorName = null,
        bool $isCheckin = false,
    ): Uuid {
        $id = Uuid::random();

        $this->commandBus->dispatch(new AddOrderCommentCommand(
            id:           $id,
            orderId:      $orderId,
            message:      $message,
            authorSource: $authorSource,
            userId:       $userId,
            authorName:   $authorName,
            isCheckin:    $isCheckin,
        ));

        return $id;
    }
}
