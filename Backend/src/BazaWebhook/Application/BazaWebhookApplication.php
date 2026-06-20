<?php

declare(strict_types=1);

namespace Tickets\BazaWebhook\Application;

use InvalidArgumentException;
use Tickets\BazaWebhook\Domain\BazaTicketEnteredEvent;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;

/**
 * Приём вебхука «билет прошёл» от Baza (Ф4). Пишет факт входа в domain_history
 * (aggregate_type='ticket', actor_type=baza). БД — только в репозитории истории.
 *
 * Идемпотентно по event_id (id строки outbox Baza): повтор того же вебхука (ретрай дренажа)
 * не создаёт дубль события.
 */
final class BazaWebhookApplication
{
    public function __construct(
        private readonly HistoryRepositoryInterface $history,
    ) {}

    /**
     * @param  array<string, mixed>  $data  тело вебхука (event_id, ticket_uuid, target, kilter, change_id, entered_at, wristband_qr)
     * @return bool true — записано; false — уже было (идемпотентный повтор)
     */
    public function recordEntry(array $data): bool
    {
        $ticketUuid = isset($data['ticket_uuid']) ? (string) $data['ticket_uuid'] : '';
        if ($ticketUuid === '') {
            throw new InvalidArgumentException('Не передан ticket_uuid');
        }

        $eventId = isset($data['event_id']) ? (string) $data['event_id'] : null;

        if ($eventId !== null && $this->alreadyRecorded($ticketUuid, $eventId)) {
            return false;
        }

        $this->history->save(new SaveHistoryDto(
            $ticketUuid,
            new BazaTicketEnteredEvent([
                'event_id' => $eventId,
                'target' => $data['target'] ?? null,
                'kilter' => isset($data['kilter']) ? (int) $data['kilter'] : null,
                'change_id' => isset($data['change_id']) ? (int) $data['change_id'] : null,
                'entered_at' => $data['entered_at'] ?? null,
                'wristband_qr' => $data['wristband_qr'] ?? null,
            ]),
            null,
            ActorType::BAZA,
        ));

        return true;
    }

    /** Уже есть событие ticket_entered этого билета с тем же event_id? */
    private function alreadyRecorded(string $ticketUuid, string $eventId): bool
    {
        foreach ($this->history->getByAggregateId($ticketUuid) as $event) {
            if ($event->eventName === 'ticket_entered' && ($event->payload['event_id'] ?? null) === $eventId) {
                return true;
            }
        }

        return false;
    }
}
