<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Application\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\EmailDelivery\Application\Support\MailDeliveryLog;
use Tickets\EmailDelivery\Domain\EmailLifecycleEvent;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Dto\EmailMessageDto;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Асинхронная отправка одного письма с обновлением статуса (Ф2).
 *
 * queued → sending → sent / failed. Несёт только id записи; сам Mailable читается из БД
 * (колонка mailable) — поэтому повторная отправка из админки = повторный dispatch этой же
 * задачи по id. tries=3 с backoff; финальный сбой → failed() помечает письмо failed.
 */
final class SendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var int[] backoff между попытками (сек): 30с / 2м / 10м. */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly string $emailMessageId)
    {
    }

    public function handle(EmailMessageRepositoryInterface $repository, HistoryRepositoryInterface $history): void
    {
        $id = new Uuid($this->emailMessageId);
        $message = $repository->findById($id);

        if ($message === null) {
            MailDeliveryLog::logger()->error('mail.not_found', ['id' => $this->emailMessageId]);

            return;
        }

        // Идемпотентность: уже отправлено/доставлено/открыто — повторно не шлём.
        if (in_array($message->getStatus(), [EmailStatus::SENT, EmailStatus::DELIVERED, EmailStatus::OPENED], true)) {
            return;
        }

        $blob = $repository->getMailableBlob($id);
        if ($blob === null) {
            $repository->markFailed($id, 'Нет сохранённого письма для отправки');
            $this->recordHistory($history, $message, EmailStatus::FAILED, ['reason' => 'no_mailable']);

            return;
        }

        /** @var Mailable $mailable */
        $mailable = unserialize(base64_decode($blob));

        $repository->markSending($id);
        $this->recordHistory($history, $message, EmailStatus::SENDING);

        try {
            Mail::to($message->getRecipient())->send($mailable);

            $repository->markSent($id, null);
            $this->recordHistory($history, $message, EmailStatus::SENT);
            MailDeliveryLog::logger()->info('mail.sent', [
                'id' => $this->emailMessageId,
                'to' => MailDeliveryLog::maskEmail($message->getRecipient()),
                'event' => $message->getEvent(),
                'source' => $message->getSource(),
            ]);
        } catch (Throwable $e) {
            $repository->markFailed($id, $e->getMessage());
            $this->recordHistory($history, $message, EmailStatus::FAILED, ['error' => $e->getMessage()]);
            MailDeliveryLog::logger()->error('mail.failed', [
                'id' => $this->emailMessageId,
                'to' => MailDeliveryLog::maskEmail($message->getRecipient()),
                'event' => $message->getEvent(),
                'error' => $e->getMessage(),
            ]);

            throw $e; // отдаём очереди на ретрай (tries/backoff)
        }
    }

    /** Окончательный сбой задачи (tries исчерпаны) — фиксируем failed. */
    public function failed(Throwable $e): void
    {
        app(EmailMessageRepositoryInterface::class)->markFailed(new Uuid($this->emailMessageId), $e->getMessage());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function recordHistory(
        HistoryRepositoryInterface $history,
        EmailMessageDto $message,
        string $status,
        array $payload = [],
    ): void {
        $history->save(new SaveHistoryDto(
            $message->getId()->value(),
            new EmailLifecycleEvent($status, $payload),
            null,
            str_starts_with($message->getSource(), 'qr') ? ActorType::QR : ActorType::SYSTEM,
        ));
    }
}
