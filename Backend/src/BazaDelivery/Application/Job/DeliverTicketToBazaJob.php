<?php

declare(strict_types=1);

namespace Tickets\BazaDelivery\Application\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\BazaDelivery\Application\Support\BazaDeliveryLog;
use Tickets\BazaDelivery\Domain\BazaDeliveryLifecycleEvent;
use Tickets\BazaDelivery\Domain\ValueObject\BazaDeliveryStatus;
use Tickets\BazaDelivery\Dto\BazaDeliveryDto;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\History\Dto\SaveHistoryDto;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;

/**
 * Асинхронная запись одного билета в Baza с трекингом каждой попытки. Зеркало SendEmailJob.
 *
 * queued → sending → delivered / failed. Несёт только id записи baza_deliveries; нужный билет
 * заново собирается из БД (getTicket по ticket_id) — поэтому повтор из админки = повторный dispatch.
 *
 * Кап = 10 попыток (§6.4): авто-ретрай и ручной resend суммарно ограничены 10 (attempts не
 * сбрасывается). После 10 неуспешных — терминальный failed, новые попытки записи в Baza не идут.
 */
final class DeliverTicketToBazaJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Жёсткий предел попыток доставки в Baza (авто-ретрай + ручной resend). */
    public const MAX_ATTEMPTS = 10;

    public int $tries = self::MAX_ATTEMPTS;

    /** @var int[] backoff между авто-ретраями (сек): 30с / 2м / 10м (далее — последнее значение). */
    public array $backoff = [30, 120, 600];

    public function __construct(private readonly string $deliveryId)
    {
    }

    public function handle(
        BazaDeliveryRepositoryInterface $repository,
        TicketsRepositoryInterface $tickets,
        HistoryRepositoryInterface $history,
    ): void {
        $id = new Uuid($this->deliveryId);
        $delivery = $repository->findById($id);

        if ($delivery === null) {
            BazaDeliveryLog::logger()->error('baza.not_found', ['id' => $this->deliveryId]);

            return;
        }

        // Идемпотентность: уже доставлено — повторно в Baza не пишем.
        if ($delivery->getStatus() === BazaDeliveryStatus::DELIVERED) {
            return;
        }

        // Кап: после MAX_ATTEMPTS попыток — терминальный failed, новых попыток нет.
        if ($delivery->getAttempts() >= self::MAX_ATTEMPTS) {
            $repository->markFailed($id, 'Достигнут предел попыток доставки в Baza (' . self::MAX_ATTEMPTS . ')');
            BazaDeliveryLog::logger()->error('baza.cap_reached', [
                'id' => $this->deliveryId,
                'attempts' => $delivery->getAttempts(),
            ]);

            return;
        }

        $repository->markSending($id);                 // attempts++
        $attemptNo = $delivery->getAttempts() + 1;
        $this->recordHistory($history, $delivery, BazaDeliveryStatus::SENDING, [
            'target' => $delivery->getTarget(),
            'attempt' => $attemptNo,
        ]);

        try {
            if (! $this->deliver($delivery, $tickets)) {
                throw new RuntimeException('Запись билета в Baza вернула false');
            }

            $repository->markDelivered($id);
            $this->recordHistory($history, $delivery, BazaDeliveryStatus::DELIVERED, [
                'target' => $delivery->getTarget(),
                'attempt' => $attemptNo,
            ]);
            BazaDeliveryLog::logger()->info('baza.delivered', [
                'id' => $this->deliveryId,
                'target' => $delivery->getTarget(),
                'ticket_id' => $delivery->getTicketId()->value(),
                'attempt' => $attemptNo,
            ]);
        } catch (Throwable $e) {
            $repository->markFailed($id, $e->getMessage());
            $this->recordHistory($history, $delivery, BazaDeliveryStatus::FAILED, [
                'target' => $delivery->getTarget(),
                'attempt' => $attemptNo,
                'error' => $e->getMessage(),
            ]);
            BazaDeliveryLog::logger()->error('baza.failed', [
                'id' => $this->deliveryId,
                'target' => $delivery->getTarget(),
                'ticket_id' => $delivery->getTicketId()->value(),
                'attempt' => $attemptNo,
                'error' => $e->getMessage(),
            ]);

            // Авто-ретрай только пока не достигли капа; на последней попытке очередь не дёргаем.
            if ($attemptNo < self::MAX_ATTEMPTS) {
                throw $e;
            }

            BazaDeliveryLog::logger()->error('baza.cap_reached', [
                'id' => $this->deliveryId,
                'attempts' => $attemptNo,
            ]);
        }
    }

    /** Маршрутизация записи в Baza по цели доставки. Билет пересобирается из БД (getTicket). */
    private function deliver(BazaDeliveryDto $delivery, TicketsRepositoryInterface $tickets): bool
    {
        return match ($delivery->getTarget()) {
            'el_tickets' => $tickets->setInBaza($tickets->getTicket($delivery->getTicketId(), true)),
            'spisok_tickets' => $tickets->setInBazaList($tickets->getTicket($delivery->getTicketId(), true)),
            'live_tickets' => $tickets->setInBazaLive((int) $delivery->getNumber(), $delivery->getTicketId()),
            default => throw new RuntimeException('Неизвестная цель доставки в Baza: ' . $delivery->getTarget()),
        };
    }

    /** Окончательный сбой задачи (tries исчерпаны) — фиксируем failed. */
    public function failed(Throwable $e): void
    {
        app(BazaDeliveryRepositoryInterface::class)->markFailed(new Uuid($this->deliveryId), $e->getMessage());
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function recordHistory(
        HistoryRepositoryInterface $history,
        BazaDeliveryDto $delivery,
        string $status,
        array $payload = [],
    ): void {
        $history->save(new SaveHistoryDto(
            $delivery->getId()->value(),
            new BazaDeliveryLifecycleEvent($status, $payload),
            null,
            str_starts_with($delivery->getSource(), 'qr') ? ActorType::QR : ActorType::SYSTEM,
        ));
    }
}
