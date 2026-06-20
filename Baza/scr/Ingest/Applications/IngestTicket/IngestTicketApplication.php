<?php

declare(strict_types=1);

namespace Baza\Ingest\Applications\IngestTicket;

use Baza\Ingest\Repositories\IngestRepositoryInterface;
use InvalidArgumentException;

/**
 * Приём билета от org (Ф3, API №ingest). Тонкий слой: валидирует цель (target) и
 * раскладывает поля по нужному upsert репозитория. БД — только в репозитории.
 *
 * Возвращает bool «применено ли»: для el/spisok/auto всегда true при успехе,
 * для live — false, если строки с номером ещё нет (org откатится на прямую запись).
 * Неизвестный target / битый контракт → InvalidArgumentException (контроллер отдаст 422).
 */
final class IngestTicketApplication
{
    public const TARGET_EL = 'el_tickets';

    public const TARGET_SPISOK = 'spisok_tickets';

    public const TARGET_LIVE = 'live_tickets';

    public const TARGET_AUTO = 'auto';

    public const TARGETS = [
        self::TARGET_EL,
        self::TARGET_SPISOK,
        self::TARGET_LIVE,
        self::TARGET_AUTO,
    ];

    public function __construct(
        private readonly IngestRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $ticket
     */
    public function ingest(string $target, array $ticket): bool
    {
        if (! in_array($target, self::TARGETS, true)) {
            throw new InvalidArgumentException("Неизвестная цель доставки: '{$target}'");
        }

        return match ($target) {
            self::TARGET_EL => $this->upsertEl($ticket),
            self::TARGET_SPISOK => $this->upsertSpisok($ticket),
            self::TARGET_LIVE => $this->linkLive($ticket),
            self::TARGET_AUTO => $this->upsertAuto($ticket),
        };
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function upsertEl(array $ticket): bool
    {
        if (empty($ticket['uuid'])) {
            throw new InvalidArgumentException('el_tickets: не передан uuid билета');
        }

        return $this->repository->upsertElTicket($ticket);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function upsertSpisok(array $ticket): bool
    {
        if (empty($ticket['ticket_uuid'])) {
            throw new InvalidArgumentException('spisok_tickets: не передан ticket_uuid');
        }

        return $this->repository->upsertSpisokTicket($ticket);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function linkLive(array $ticket): bool
    {
        $kilter = isset($ticket['kilter']) ? (int) $ticket['kilter'] : (int) ($ticket['number'] ?? 0);
        if ($kilter <= 0) {
            throw new InvalidArgumentException('live_tickets: не передан номер (kilter)');
        }

        $elTicketId = isset($ticket['el_ticket_id']) ? (string) $ticket['el_ticket_id'] : null;

        return $this->repository->linkLiveTicket($kilter, $elTicketId !== '' ? $elTicketId : null);
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function upsertAuto(array $ticket): bool
    {
        if (empty($ticket['order_id']) || empty($ticket['auto'])) {
            throw new InvalidArgumentException('auto: нужны order_id и auto (номер)');
        }

        return $this->repository->upsertAuto($ticket);
    }
}
