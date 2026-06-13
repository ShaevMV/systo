<?php

declare(strict_types=1);

namespace App\Console\Commands\Proto;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Shared\Integration\Rabbit\EventConsumer;
use Shared\Integration\Rabbit\EventEnvelope;
use Shared\Integration\Rabbit\EventSigner;
use Shared\Integration\Rabbit\RabbitConnectionFactory;

/**
 * ПРОТОТИП. Консьюмер билетов из RabbitMQ (org → шина → BAZA).
 *
 * BAZA как «система входа» принимает событие `ticket.issued` от org, проверяет подпись
 * и свежесть (anti-replay), дедуплицирует по idempotency_key и СОХРАНЯЕТ билет ЛОКАЛЬНО
 * в `el_tickets`. Это замена прямой записи org → БД BAZA (`DB::connection('mysqlBaza')`)
 * на событийный канал. BAZA хранит билет у себя → пускает на вход офлайн (брокер не нужен в день фестиваля).
 *
 * Принцип «BAZA валидирует каждую команду» (см. RFC §6): payload проверяется до записи;
 * битый payload → reject без requeue (не зацикливаем).
 *
 * Пример (прогон до тишины 5с):
 *   php artisan proto:consume-tickets --idle=5
 * Постоянный воркер:
 *   php artisan proto:consume-tickets
 */
final class ConsumeTicketsCommand extends Command
{
    protected $signature = 'proto:consume-tickets
        {--max= : остановиться после N обработанных (по умолчанию бесконечно)}
        {--idle=0 : выйти, если N секунд нет сообщений (0 — ждать бесконечно)}';

    protected $description = '[ПРОТОТИП] Консьюмер ticket.issued из RabbitMQ → запись в el_tickets';

    /** Обязательные поля билета (все NOT NULL в el_tickets). */
    private const REQUIRED = ['uuid', 'kilter', 'name', 'email', 'phone', 'city', 'date_order', 'status', 'type_ticket_id', 'type_ticket'];

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

        $queue = (string) $config['tickets_queue'];
        $this->info(sprintf('Слушаю очередь "%s" (exchange "%s", ключ ticket.issued)...', $queue, $config['exchange']));

        $logger = function (string $level, string $message): void {
            $level === 'error' ? $this->error($message) : $this->warn($message);
        };

        $processed = $consumer->consume(
            queue: $queue,
            bindingKeys: ['ticket.issued'],
            handler: fn (EventEnvelope $e): bool => $this->handleTicket($e),
            maxMessages: $this->option('max') !== null && $this->option('max') !== '' ? (int) $this->option('max') : null,
            idleTimeout: (int) $this->option('idle'),
            logger: $logger,
        );

        $this->info(sprintf('Обработано сообщений: %d', $processed));

        return self::SUCCESS;
    }

    /**
     * Обработать одно событие ticket.issued. Возврат: true — обработано/дубликат (ack),
     * false — бизнес-отказ (reject без requeue).
     */
    private function handleTicket(EventEnvelope $envelope): bool
    {
        if ($envelope->eventType !== 'ticket.issued') {
            $this->warn('Пропущен неожиданный тип события: ' . $envelope->eventType);

            return true; // не наш тип — ack, чтобы не зациклить
        }

        $payload = $envelope->payload;
        foreach (self::REQUIRED as $key) {
            if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
                $this->error(sprintf('Отклонён билет %s: нет обязательного поля "%s"', $envelope->idempotencyKey, $key));

                return false;
            }
        }

        return DB::transaction(function () use ($envelope, $payload): bool {
            // Дедупликация: повторная доставка того же события не создаёт дубль.
            $already = DB::table('processed_messages')
                ->where('idempotency_key', $envelope->idempotencyKey)
                ->exists();
            if ($already) {
                $this->warn('Дубликат (уже обработано): ' . $envelope->idempotencyKey);

                return true;
            }

            $now = Carbon::now();
            DB::table('el_tickets')->updateOrInsert(
                ['uuid' => $payload['uuid']],
                [
                    'kilter' => (int) $payload['kilter'],
                    'city' => (string) $payload['city'],
                    'name' => (string) $payload['name'],
                    'email' => (string) $payload['email'],
                    'phone' => (string) $payload['phone'],
                    'date_order' => (string) $payload['date_order'],
                    'status' => (string) $payload['status'],
                    'comment' => $payload['comment'] ?? null,
                    'is_need_seedling' => (bool) ($payload['is_need_seedling'] ?? false),
                    'type_ticket' => $payload['type_ticket'] ?? null,
                    'type_ticket_id' => $payload['type_ticket_id'] ?? null,
                    'festival_id' => $payload['festival_id'] ?? null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );

            DB::table('processed_messages')->insert([
                'idempotency_key' => $envelope->idempotencyKey,
                'event_type' => $envelope->eventType,
                'source' => $envelope->source,
                'trace_id' => $envelope->traceId,
                'processed_at' => $now,
            ]);

            $this->info(sprintf('Билет сохранён в el_tickets: uuid=%s kilter=%s', $payload['uuid'], $payload['kilter']));

            return true;
        });
    }
}
