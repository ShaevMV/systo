<?php

declare(strict_types=1);

namespace App\Console\Commands\Proto;

use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Shared\Integration\Rabbit\EventEnvelope;
use Shared\Integration\Rabbit\EventPublisher;
use Shared\Integration\Rabbit\EventSigner;
use Shared\Integration\Rabbit\RabbitConnectionFactory;

/**
 * ПРОТОТИП. Публикует событие `ticket.issued` в RabbitMQ (org → шина → BAZA).
 *
 * Демонстрирует решение разворота: org как «создатель/отправитель билета» публикует
 * факт выпуска билета в шину; BAZA («система входа») консьюмит и сохраняет билет локально
 * (см. `proto:consume-tickets` в Baza). Заменяет прямую запись org → БД BAZA.
 *
 * Для прототипа payload синтетический (генерится тут), чтобы прогон не требовал
 * реального оплаченного заказа. Формат payload повторяет TicketResponse::toArrayForBaza().
 *
 * Пример:
 *   php artisan proto:publish-ticket --name="Иван Иванов" --email=ivan@example.com
 */
final class PublishTicketCommand extends Command
{
    protected $signature = 'proto:publish-ticket
        {--name=Тест Гость : ФИО гостя}
        {--email=guest@example.com : email гостя}
        {--phone=+70000000000 : телефон}
        {--city=Москва : город}
        {--kilter= : kilter (по умолчанию случайный)}
        {--uuid= : uuid билета (по умолчанию случайный)}
        {--type-ticket-id=c3d4e5f6-a7b8-9012-cdef-345678901235 : UUID типа билета (NOT NULL в BAZA)}
        {--type-ticket=Оргвзнос (прототип) : название типа билета (NOT NULL в BAZA)}';

    protected $description = '[ПРОТОТИП] Опубликовать событие ticket.issued в RabbitMQ';

    public function handle(): int
    {
        $config = config('rabbitmq');

        if (($config['signing_secret'] ?? '') === '') {
            $this->error('RABBITMQ_SIGNING_SECRET не задан в .env — публикация подписанных событий невозможна.');

            return self::FAILURE;
        }

        $ticketUuid = $this->option('uuid') ?: RamseyUuid::uuid4()->toString();
        $kilter = $this->option('kilter') !== null && $this->option('kilter') !== ''
            ? (int) $this->option('kilter')
            : random_int(100000, 999999);

        // payload по форме TicketResponse::toArrayForBaza() (поля el_tickets в BAZA).
        $payload = [
            'kilter' => $kilter,
            'uuid' => $ticketUuid,
            'name' => (string) $this->option('name'),
            'email' => (string) $this->option('email'),
            'phone' => (string) $this->option('phone'),
            'city' => (string) $this->option('city'),
            'date_order' => now()->toDateTimeString(),
            'status' => 'paid',
            'comment' => null,
            'is_need_seedling' => false,
            'type_ticket' => (string) $this->option('type-ticket'),
            'type_ticket_id' => (string) $this->option('type-ticket-id'),
            'festival_id' => null,
        ];

        $envelope = new EventEnvelope(
            eventType: 'ticket.issued',
            traceId: RamseyUuid::uuid4()->toString(),
            // idempotency_key стабилен для билета — повторная публикация не создаст дубль в BAZA.
            idempotencyKey: 'ticket.issued:' . $ticketUuid,
            occurredAt: now()->toIso8601String(),
            source: 'org',
            payload: $payload,
        );

        $publisher = new EventPublisher(
            RabbitConnectionFactory::fromConfig($config),
            new EventSigner($config['signing_secret'], (int) $config['max_skew_seconds']),
            (string) $config['exchange'],
        );

        $traceId = $publisher->publish($envelope, time());

        $this->info('Опубликовано ticket.issued');
        $this->line('  uuid:            ' . $ticketUuid);
        $this->line('  kilter:          ' . $kilter);
        $this->line('  idempotency_key: ' . $envelope->idempotencyKey);
        $this->line('  trace_id:        ' . $traceId);

        return self::SUCCESS;
    }
}
