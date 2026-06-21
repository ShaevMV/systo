<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\AddComment;

use Shared\Domain\Bus\Command\CommandHandler;
use Tickets\History\Domain\ActorType;
use Tickets\History\Domain\Event\OrderCommentAddedEvent;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Order\OrderTicket\Dto\CommentDto;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Tickets\Order\OrderTicket\ValueObject\CommentSource;

/**
 * Добавляет запись в тред комментариев заказа (БД — только через репозиторий) и
 * пишет событие истории `comment_added` (без ПДн — только источник/флаги).
 */
class AddOrderCommentCommandHandler implements CommandHandler
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
        private HistoryRepositoryInterface $historyRepository,
    ) {
    }

    public function __invoke(AddOrderCommentCommand $command): void
    {
        $this->commentRepository->addComment(new CommentDto(
            $command->getId(),
            $command->getUserId(),
            $command->getOrderId(),
            $command->getMessage(),
            $command->getAuthorName(),
            $command->getAuthorSource(),
            $command->isCheckin(),
        ));

        // actor_type истории выводим из источника комментария: org_user → user (актёр = его id),
        // остальные источники (baza/qr/system) — соответствующий S2S/системный тип, actor_id = null.
        [$actorType, $actorId] = $this->resolveActor($command);

        $this->historyRepository->save(new SaveHistoryDto(
            aggregateId: $command->getOrderId()->value(),
            event:       new OrderCommentAddedEvent(
                $command->getAuthorSource(),
                mb_strlen($command->getMessage()),
            ),
            actorId:     $actorId,
            actorType:   $actorType,
        ));
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function resolveActor(AddOrderCommentCommand $command): array
    {
        return match ($command->getAuthorSource()) {
            CommentSource::ORG_USER => [ActorType::USER, $command->getUserId()?->value()],
            CommentSource::BAZA     => [ActorType::BAZA, null],
            CommentSource::QR       => [ActorType::QR, null],
            default                 => [ActorType::SYSTEM, null],
        };
    }
}
