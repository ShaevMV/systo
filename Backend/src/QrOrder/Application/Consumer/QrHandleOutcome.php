<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Consumer;

/**
 * Исход обработки одного AMQP-сообщения консьюмером qr→org.
 * Команда qr:consume отображает его на действие с сообщением:
 *   - Ack   → message->ack() (обработано успешно);
 *   - Dlq   → reject(requeue=false) → DLX-политика → q.qr.dlq (битый/непригодный, НЕ ретраить);
 *   - Retry → повтор (requeue) до предела попыток по x-delivery-count, затем в DLQ
 *             (транзиентный сбой: БД недоступна, заказ ещё не создан и т.п.).
 */
enum QrHandleOutcome: string
{
    case Ack = 'ack';
    case Dlq = 'dlq';
    case Retry = 'retry';
}
