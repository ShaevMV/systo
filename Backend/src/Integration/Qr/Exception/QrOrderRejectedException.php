<?php

declare(strict_types=1);

namespace Tickets\Integration\Qr\Exception;

use RuntimeException;

/**
 * Перманентный бизнес-отказ при приёме заказа qr → org (невалидный контракт,
 * несуществующий festival/option/тип, расхождение, которое нельзя исправить ретраем).
 *
 * Консьюмер трактует его как reject БЕЗ requeue (сообщение не зацикливается).
 * Транзиентные сбои (БД недоступна и т.п.) бросаются обычными исключениями — те идут в requeue.
 */
final class QrOrderRejectedException extends RuntimeException
{
}
