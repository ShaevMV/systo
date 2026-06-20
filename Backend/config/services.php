<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Авто-одобрение заказа на /api/v1/order/create по заголовку "AutoPayment".
    // Если токен пустой — фича выключена.
    'auto_payment' => [
        'token' => env('AUTO_PAYMENT_TOKEN'),
    ],

    // Сервисные ключи S2S-канала приёма заказов от витрины qr.spaceofjoy.ru
    // (POST /api/v1/qrOrder/create, заголовок "X-QR-Token").
    // Список через запятую — на время ротации можно держать старый + новый ключ одновременно.
    // Пустой список = канал закрыт (безопасный дефолт): без валидного ключа доступа нет.
    'qr_ingest' => [
        'tokens' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('QR_INGEST_TOKENS', '')),
        ))),
    ],

    // Исходящий S2S-канал записи билета в Baza через ingest-API (Ф3).
    // Заданы И url, И token → доставка идёт через API (POST {url}/api/baza/ingest/ticket,
    // заголовок X-Baza-Token), при сбое — fallback на прямую запись в БД Baza.
    // Пусто (по умолчанию) → канал выключен, поведение org не меняется (только прямая запись).
    'baza_ingest' => [
        'url' => env('BAZA_INGEST_URL'),
        'token' => env('BAZA_INGEST_TOKEN'),
    ],

    // ВХОДЯЩИЙ S2S-канал приёма вебхука «билет прошёл» от Baza (Ф4, заголовок X-Baza-Token).
    // Список валидных ключей (через запятую). Зеркало qr_ingest. Отдельный от исходящего
    // baza_ingest (org→Baza). Пустой список → канал закрыт (безопасный дефолт).
    'baza_webhook' => [
        'tokens' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('BAZA_WEBHOOK_TOKENS', '')),
        ))),
    ],

];
