<?php

declare(strict_types=1);

namespace Baza\EntryOutbox\Applications;

use Baza\EntryOutbox\Repositories\EntryOutboxRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Тонкий слой outbox вебхука входа (Ф4): запись при впуске + дренаж на org.
 * БД — только в репозитории.
 */
final class EntryOutboxApplication
{
    /** Кап попыток отправки вебхука (авто-ретрай дренажа). */
    public const MAX_ATTEMPTS = 15;

    /** Размер батча дренажа за один запуск. */
    private const BATCH = 100;

    public function __construct(
        private readonly EntryOutboxRepositoryInterface $repository,
        private readonly OrgWebhookClient $client,
    ) {}

    /**
     * Записать факт входа билета в буфер. Best-effort: НЕ бросает — впуск не должен падать
     * из-за вебхука. Тип/id — те же, что в EnterTicket::skip (el/spisok/live по kilter, auto по id).
     */
    public function record(string $type, int $id, ?int $changeId): void
    {
        try {
            $target = $this->repository->targetForType($type);
            if ($target === null) {
                return; // тип без вебхука (drug/parking) — пропускаем
            }

            $ticketUuid = $this->repository->resolveTicketUuid($type, $id);
            if ($ticketUuid === null) {
                // Нет org-идентификатора (напр. live без el_ticket_id) — org не свяжет, не пишем.
                Log::info('baza.outbox.no_org_id', ['type' => $type, 'id' => $id]);

                return;
            }

            $this->repository->enqueue(
                $target,
                $ticketUuid,
                $type === 'auto' ? null : $id,
                $changeId,
                Carbon::now()->format('Y-m-d H:i:s'),
            );
        } catch (Throwable $e) {
            // Глушим: впуск уже состоялся, буфер — побочный канал.
            Log::error('baza.outbox.record_failed', ['type' => $type, 'id' => $id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Слить буфер на org. Возвращает число успешно отправленных. Канал выключен → 0 (буфер копится).
     */
    public function drain(): int
    {
        if (! $this->client->isEnabled()) {
            return 0;
        }

        $sent = 0;
        foreach ($this->repository->pending(self::BATCH, self::MAX_ATTEMPTS) as $row) {
            $this->repository->markSending($row->id);
            $result = $this->client->send($row);

            if ($result === true) {
                $this->repository->markSent($row->id);
                $sent++;
            } else {
                $this->repository->markFailed($row->id, $result === false ? 'org вернул success=false' : 'нет связи с org');
            }
        }

        return $sent;
    }
}
