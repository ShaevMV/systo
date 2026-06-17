<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application;

use Illuminate\Mail\Mailable;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Application\Job\SendEmailJob;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Domain\EmailLifecycleEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Dto\EmailMessageDto;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Единая точка отправки письма с трекингом (Ф2). Создаёт запись email_messages(queued),
 * сохраняет сериализованный Mailable (для отправки/повтора), пишет историю и ставит
 * асинхронную задачу SendEmailJob. Контроль полного пути письма начинается здесь.
 *
 * Slug в записи — информативный (дефолт события); фактический шаблон qr-письма выбирается
 * внутри Mailable через emailView (event-aware привязка из Ф1, см. getTicket).
 */
final class MailDispatcher
{
    public function __construct(
        private readonly EmailMessageRepositoryInterface $repository,
        private readonly HistoryRepositoryInterface $history,
    ) {
    }

    /**
     * Поставить письмо в очередь с трекингом. Возвращает id записи email_messages.
     */
    public function send(string $event, EmailContext $ctx, Mailable $mailable): Uuid
    {
        $id = Uuid::random();
        $token = bin2hex(random_bytes(16));
        $slug = EmailEvent::defaultSlug($event) ?? $event;

        $this->repository->create(
            EmailMessageDto::queued($id, $event, $ctx, $slug, $token),
            base64_encode(serialize($mailable)),
        );

        $this->history->save(new SaveHistoryDto(
            $id->value(),
            new EmailLifecycleEvent(EmailStatus::QUEUED, ['event' => $event, 'source' => $ctx->source]),
            null,
            $ctx->actorType,
        ));

        SendEmailJob::dispatch($id->value());

        return $id;
    }
}
