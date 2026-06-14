<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr;

use Shared\Integration\Rabbit\EventEnvelope;

/**
 * Приём заказа от внешней витрины qr.spaceofjoy.ru: превращает событие `order.created`
 * во внутренний заказ org (создание заказа → билеты → PDF/QR → письма → история).
 *
 * Транспорт, дедупликация и проверка подписи — НЕ его ответственность (это {@see QrOrderConsumer}
 * и EventConsumer). Ингестор получает уже проверенный конверт и делает бизнес-эффект.
 *
 * Реализации:
 *  - {@see LoggingQrOrderIngestor} — заглушка Фазы 1 (логирует, ничего не создаёт).
 *  - боевая реализация — Фаза 2 (QrOrderAssembler) + Фаза 3 (ingest-pipeline).
 *
 * @throws \Tickets\Integration\Qr\Exception\QrOrderRejectedException перманентный бизнес-отказ (reject без requeue)
 */
interface QrOrderIngestorInterface
{
    public function ingest(EventEnvelope $envelope): void;
}
