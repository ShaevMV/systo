<?php

declare(strict_types=1);

namespace App\Console\Commands\Integration;

use Illuminate\Console\Command;
use Shared\Integration\Rabbit\EventConsumer;
use Shared\Integration\Rabbit\EventEnvelope;
use Shared\Integration\Rabbit\EventSigner;
use Shared\Integration\Rabbit\RabbitConnectionFactory;
use Tickets\Integration\Qr\QrOrderConsumer;

/**
 * Консьюмер заказов из внешней витрины qr.spaceofjoy.ru (qr → шина → org).
 *
 * Слушает очередь org.qr-orders (binding `order.created`), проверяет подпись + anti-replay
 * (EventConsumer), затем передаёт конверт в {@see QrOrderConsumer} (проверка схемы, дедуп,
 * создание заказа через ингестор). См. CONTRACT_RFC_v0.md §7.
 *
 * Фаза 1: ингестор — заглушка (LoggingQrOrderIngestor), реальный приём заказа — Ф2/Ф3.
 *
 * Постоянный воркер (под supervisord, как queue:work):
 *   php artisan qr:consume-orders
 * Разовый прогон до тишины 5с (для проверки на стенде):
 *   php artisan qr:consume-orders --idle=5
 */
final class ConsumeQrOrdersCommand extends Command
{
    protected $signature = 'qr:consume-orders
        {--max= : остановиться после N обработанных (по умолчанию бесконечно)}
        {--idle=0 : выйти, если N секунд нет сообщений (0 — ждать бесконечно)}';

    protected $description = 'Консьюмер order.created из RabbitMQ (qr → org): приём заказов внешней витрины';

    public function __construct(
        private readonly QrOrderConsumer $orderConsumer,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $config = config('rabbitmq');

        if (($config['signing_secret'] ?? '') === '') {
            $this->error('RABBITMQ_SIGNING_SECRET не задан в .env — проверка подписи невозможна.');

            return self::FAILURE;
        }

        $consumer = new EventConsumer(
            RabbitConnectionFactory::fromConfig($config),
            new EventSigner($config['signing_secret'], (int) $config['max_skew_seconds']),
            (string) $config['exchange'],
        );

        $queue = (string) $config['qr_orders_queue'];
        $this->info(sprintf('Слушаю очередь "%s" (exchange "%s", ключ order.created)...', $queue, $config['exchange']));

        $logger = function (string $level, string $message): void {
            $level === 'error' ? $this->error($message) : $this->warn($message);
        };

        $processed = $consumer->consume(
            queue: $queue,
            bindingKeys: ['order.created'],
            handler: fn (EventEnvelope $e): bool => $this->orderConsumer->handle($e),
            maxMessages: $this->option('max') !== null && $this->option('max') !== '' ? (int) $this->option('max') : null,
            idleTimeout: (int) $this->option('idle'),
            logger: $logger,
        );

        $this->info(sprintf('Обработано сообщений: %d', $processed));

        return self::SUCCESS;
    }
}
