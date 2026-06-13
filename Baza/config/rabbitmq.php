<?php

declare(strict_types=1);

/**
 * Конфиг RabbitMQ для прототипа шины qr ↔ org ↔ BAZA (сторона BAZA — консьюмер билетов).
 * Значения host/exchange/secret ДОЛЖНЫ совпадать с org. См. .claude/specs/qr-integration/.
 */
return [
    // RabbitMQ на ОТДЕЛЬНОМ сервере — host/креды из env. Локальный контейнер 'rabbitmq' только для дева.
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'systo'),
    'password' => env('RABBITMQ_PASSWORD', 'systo'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    // TLS для межсерверного трафика (несёт ПДн + команды). В staging/prod: RABBITMQ_SSL=true.
    'ssl' => (bool) env('RABBITMQ_SSL', false),
    'ssl_options' => array_filter([
        'cafile' => env('RABBITMQ_SSL_CAFILE'),
        'local_cert' => env('RABBITMQ_SSL_LOCAL_CERT'),
        'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
    ], static fn ($v) => $v !== null),

    'exchange' => env('RABBITMQ_EXCHANGE', 'systo.events'),

    // Очередь BAZA для входящих билетов + ключи привязки.
    'tickets_queue' => env('RABBITMQ_TICKETS_QUEUE', 'baza.tickets'),

    'signing_secret' => env('RABBITMQ_SIGNING_SECRET', ''),
    'max_skew_seconds' => (int) env('RABBITMQ_MAX_SKEW', 300),
];
