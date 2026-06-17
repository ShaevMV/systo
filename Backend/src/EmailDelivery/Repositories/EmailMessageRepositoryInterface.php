<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Dto\EmailMessageDto;

/**
 * Хранилище трекинга писем (Ф2). БД только здесь (правило №1). Текущий статус письма;
 * таймлайн событий — в domain_history через HistoryRepositoryInterface.
 */
interface EmailMessageRepositoryInterface
{
    /** Создать запись письма (status=queued) + сохранить сериализованный Mailable для (повторной) отправки. */
    public function create(EmailMessageDto $dto, ?string $mailableBlob): bool;

    public function findById(Uuid $id): ?EmailMessageDto;

    /** Письмо по токену пикселя прочтения (Ф3). */
    public function findByToken(string $token): ?EmailMessageDto;

    /** base64(serialize(Mailable)) для отправки в SendEmailJob/повтора, или null. */
    public function getMailableBlob(Uuid $id): ?string;

    /** status → sending, attempts++. */
    public function markSending(Uuid $id): bool;

    /** status → sent, sent_at = now (+ message-id транспорта, если есть). */
    public function markSent(Uuid $id, ?string $providerMessageId): bool;

    /** status → failed, error = причина. */
    public function markFailed(Uuid $id, string $error): bool;

    /** status → queued (повторная отправка из админки). */
    public function requeue(Uuid $id): bool;

    /** status → opened, opened_at = now (идемпотентно — только из sent/delivered, Ф3). */
    public function markOpened(Uuid $id): bool;

    /** Страница списка для админки (проекции EmailMessageItemForListResponse). */
    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection;

    public function countList(Filters $filters): int;

    /** Письма агрегата (для экрана qr — «линк на данные по почтам заказа»). */
    public function getByAggregate(string $aggregateType, Uuid $aggregateId): Collection;
}
