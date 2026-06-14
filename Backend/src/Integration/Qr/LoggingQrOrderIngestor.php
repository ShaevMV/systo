<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr;

use Psr\Log\LoggerInterface;
use Shared\Integration\Rabbit\EventEnvelope;

/**
 * ЗАГЛУШКА Фазы 1. Логирует факт приёма заказа от qr, но НЕ создаёт заказ/билеты.
 *
 * Нужна, чтобы протестировать транспорт + подпись + дедуп + проверку схемы сквозным прогоном
 * (`php artisan qr:consume-orders`) до готовности боевого ингестора (Фаза 2: QrOrderAssembler,
 * Фаза 3: создание заказа через фабрики OrderTicket). Перед боевым деплоем bind в TicketsProvider
 * переключается на реальную реализацию.
 */
final class LoggingQrOrderIngestor implements QrOrderIngestorInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function ingest(EventEnvelope $envelope): void
    {
        $order = $envelope->payload['order'] ?? [];
        $guests = $envelope->payload['guests'] ?? [];

        $this->logger->info('[qr-ingest:Ф1-заглушка] Принят order.created — создание заказа будет в Ф2/Ф3', [
            'trace_id' => $envelope->traceId,
            'idempotency_key' => $envelope->idempotencyKey,
            'qr_order_id' => $order['qr_order_id'] ?? null,
            'type_order' => $order['type_order'] ?? null,
            'festival_id' => $order['festival_id'] ?? null,
            'guests_count' => is_array($guests) ? count($guests) : 0,
        ]);
    }
}
