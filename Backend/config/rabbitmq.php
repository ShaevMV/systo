<?php

declare(strict_types=1);

/**
 * Конфиг RabbitMQ для прототипа шины qr ↔ org ↔ BAZA.
 * См. .claude/specs/qr-integration/CONTRACT_RFC_v0.md
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

    // Topic-exchange, в который публикуются доменные события.
    'exchange' => env('RABBITMQ_EXCHANGE', 'systo.events'),

    // Очередь приёма заказов от внешней витрины qr (qr → org), binding `order.created`.
    'qr_orders_queue' => env('RABBITMQ_QR_ORDERS_QUEUE', 'org.qr-orders'),

    // Общий секрет HMAC-подписи сообщений между системами. ОБЯЗАТЕЛЬНО задать в .env.
    // Должен совпадать на стороне org, BAZA и qr.
    'signing_secret' => env('RABBITMQ_SIGNING_SECRET', ''),

    // Окно anti-replay в секундах (допустимый разбег часов между системами).
    'max_skew_seconds' => (int) env('RABBITMQ_MAX_SKEW', 300),
];
