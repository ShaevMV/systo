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

    /*
    |--------------------------------------------------------------------------
    | Baza Ingest (S2S-приём билетов от org, Ф3)
    |--------------------------------------------------------------------------
    | Список валидных ключей (через запятую) для заголовка X-Baza-Token.
    | Зеркало org services.qr_ingest. Пустой список → канал закрыт (безопасный дефолт).
    */
    'baza_ingest' => [
        'tokens' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('BAZA_INGEST_TOKENS', '')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Org Webhook (исходящий вебхук «билет прошёл» Baza→org, Ф4)
    |--------------------------------------------------------------------------
    | Заданы И url, И token → дренаж шлёт POST {url}/api/v1/baza/ticketEntered
    | (заголовок X-Baza-Token). Пусто → канал выключен, буфер копится локально.
    */
    'org_webhook' => [
        'url' => env('ORG_WEBHOOK_URL'),
        'token' => env('ORG_WEBHOOK_TOKEN'),
    ],

];
