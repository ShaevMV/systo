<?php

declare(strict_types=1);

namespace Baza\Ingest\Applications\IngestTicket;

use Baza\Ingest\Repositories\IngestRepositoryInterface;
use Baza\Shared\Services\DefineService;
use Baza\Tickets\Repositories\TicketSearchRepositoryInterface;
use InvalidArgumentException;

/**
 * Приём билета от org (Ф3, API №ingest). Тонкий слой: валидирует цель (target) и
 * раскладывает поля по нужному upsert репозитория. БД — только в репозитории.
 *
 * Возвращает bool «применено ли»: для el/spisok/auto всегда true при успехе,
 * для live — false, если строки с номером ещё нет (org откатится на прямую запись).
 * Неизвестный target / битый контракт → InvalidArgumentException (контроллер отдаст 422).
 *
 * Помимо записи в таблицу впуска наполняет поисковый индекс `ticket_search` (богатыми
 * полями из опционального блока `search`, иначе — узким fallback из `ticket`) — для ручного
 * поиска на КПП по всем полям, когда у гостя нет QR.
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

    /** target (контракт ingest) → тип билета (DefineService, для моста к впуску). */
    private const TARGET_TO_TYPE = [
        self::TARGET_EL => DefineService::ELECTRON_TICKET,
        self::TARGET_SPISOK => DefineService::SPISOK_TICKET,
        self::TARGET_LIVE => DefineService::LIVE_TICKET,
        self::TARGET_AUTO => DefineService::AUTO_TICKET,
    ];

    public function __construct(
        private readonly IngestRepositoryInterface $repository,
        private readonly TicketSearchRepositoryInterface $ticketSearch,
    ) {}

    /**
     * @param  array<string, mixed>  $ticket  поля для таблицы впуска (узкий контракт)
     * @param  array<string, mixed>  $search  опц. богатые поля для поиска (fio/phone/telegram/car/child/...)
     */
    public function ingest(string $target, array $ticket, array $search = []): bool
    {
        if (! in_array($target, self::TARGETS, true)) {
            throw new InvalidArgumentException("Неизвестная цель доставки: '{$target}'");
        }

        $applied = match ($target) {
            self::TARGET_EL => $this->upsertEl($ticket),
            self::TARGET_SPISOK => $this->upsertSpisok($ticket),
            self::TARGET_LIVE => $this->linkLive($ticket),
            self::TARGET_AUTO => $this->upsertAuto($ticket),
        };

        // Поисковый индекс — независимо от результата upsert (self-guard на отсутствие uuid внутри).
        $this->indexSearch($target, $ticket, $search);

        return $applied;
    }

    /**
     * Наполнить ticket_search: ticket_uuid по типу + проекция искомых полей (rich `search`
     * с fallback на узкий `ticket`). Нет org-идентификатора (напр. live без el_ticket_id) → пропуск.
     *
     * @param  array<string, mixed>  $ticket
     * @param  array<string, mixed>  $search
     */
    private function indexSearch(string $target, array $ticket, array $search): void
    {
        $ticketUuid = match ($target) {
            self::TARGET_EL => (string) ($ticket['uuid'] ?? ''),
            self::TARGET_SPISOK => (string) ($ticket['ticket_uuid'] ?? ''),
            self::TARGET_LIVE => (string) ($ticket['el_ticket_id'] ?? ''),
            self::TARGET_AUTO => (string) ($ticket['order_id'] ?? ''),
            default => '',
        };

        if ($ticketUuid === '') {
            return;
        }

        $this->ticketSearch->index([
            'ticket_uuid' => $ticketUuid,
            'festival_id' => $ticket['festival_id'] ?? ($search['festival_id'] ?? null),
            'type' => self::TARGET_TO_TYPE[$target],
            'kilter' => $ticket['kilter'] ?? null,
            'fio' => $search['fio'] ?? ($ticket['name'] ?? null),
            'phone' => $search['phone'] ?? ($ticket['phone'] ?? null),
            'telegram' => $search['telegram'] ?? null,
            'email' => $search['email'] ?? ($ticket['email'] ?? null),
            'city' => $search['city'] ?? ($ticket['city'] ?? null),
            'car_number' => $search['car_number'] ?? null,
            'child_name' => $search['child_name'] ?? null,
            'parent_phone' => $search['parent_phone'] ?? null,
            'external_order_no' => $search['external_order_no'] ?? null,
            'type_ticket' => $search['type_ticket'] ?? ($ticket['type_ticket'] ?? null),
            'payload' => $search !== [] ? $search : $ticket,
        ]);
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
