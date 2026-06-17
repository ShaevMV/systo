<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\QueryBus;
use Shared\Domain\ValueObject\Uuid;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\EmailDelivery\Application\GetList\EmailMessageGetListQuery;
use Tickets\EmailDelivery\Application\GetList\EmailMessageGetListQueryHandler;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\EmailDelivery\Domain\EmailLifecycleEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Dto\EmailMessageDto;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\EmailDelivery\Responses\EmailMessageGetListResponse;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Тонкий слой админ-чтения и управления доставкой писем (Ф2). Чтение списка — через QueryBus
 * (как Location/QrOrder); БД — только в репозитории. Отправку выполняет MailDispatcher/SendEmailJob.
 */
final class EmailDeliveryApplication
{
    private readonly QueryBus $queryBus;

    public function __construct(
        private readonly EmailMessageRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
        EmailMessageGetListQueryHandler $getListQueryHandler,
    ) {
        $this->queryBus = new InMemorySymfonyQueryBus([
            EmailMessageGetListQuery::class => $getListQueryHandler,
        ]);
    }

    public function getList(EmailMessageGetListQuery $query): EmailMessageGetListResponse
    {
        return $this->queryBus->ask($query);
    }

    public function getItem(Uuid $id): ?EmailMessageDto
    {
        return $this->repository->findById($id);
    }

    /** Письма агрегата (для экрана qr — «весь путь» заказа). */
    public function getByAggregate(string $aggregateType, Uuid $aggregateId): Collection
    {
        return $this->repository->getByAggregate($aggregateType, $aggregateId);
    }

    /**
     * Повторная отправка письма из админки: возвращает письмо в очередь и ставит SendEmailJob
     * (он перечитает сохранённый Mailable из БД и отправит заново). false → письмо не найдено.
     */
    public function resend(Uuid $id, ?string $actorId): bool
    {
        $message = $this->repository->findById($id);
        if ($message === null) {
            return false;
        }

        $this->repository->requeue($id);
        $this->history->save(new SaveHistoryDto(
            $id->value(),
            new EmailLifecycleEvent(EmailStatus::QUEUED, ['action' => 'resend']),
            $actorId,
            ActorType::USER,
        ));

        SendEmailJob::dispatch($id->value());

        return true;
    }

    /**
     * Отметить письмо прочитанным по токену пикселя (Ф3). Открыть можно лишь отправленное/
     * доставленное; идемпотентно (повторный пиксель не меняет opened_at и не плодит историю).
     */
    public function registerOpen(string $token): void
    {
        $message = $this->repository->findByToken($token);
        if ($message === null) {
            return;
        }

        if (! in_array($message->getStatus(), [EmailStatus::SENT, EmailStatus::DELIVERED], true)) {
            return;
        }

        if ($this->repository->markOpened($message->getId())) {
            $this->history->save(new SaveHistoryDto(
                $message->getId()->value(),
                new EmailLifecycleEvent(EmailStatus::OPENED),
                null,
                ActorType::SYSTEM,
            ));
        }
    }
}
