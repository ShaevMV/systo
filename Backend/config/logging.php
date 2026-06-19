<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        // Канал pipeline выдачи билетов по заказам qr (структурированный JSON, без ПДн). См. TD-10.
        'qr_pipeline' => [
            'driver' => 'daily',
            'path' => storage_path('logs/qr_pipeline.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            // Сбой записи лога не должен ронять выдачу билетов (job).
            'ignore_exceptions' => true,
        ],

        // Access-лог коннектов от витрины qr к каналу приёма заказов (qrOrder/create):
        // каждый запрос — принятый и отклонённый (401) — с пометкой actor=qr (отделение от
        // действий админов). Структурированный JSON, без ПДн и без самого ключа X-QR-Token.
        'qr_access' => [
            'driver' => 'daily',
            'path' => storage_path('logs/qr_access.log'),
            // Уровень фиксирован (НЕ из глобального LOG_LEVEL): иначе на проде с LOG_LEVEL=warning
            // принятые коннекты (info) не записались бы — а нужен КАЖДЫЙ коннект (accepted + rejected).
            'level' => 'debug',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            // Сбой записи лога не должен ронять приём заказа (второй барьер к try-catch в QrAccessLog).
            'ignore_exceptions' => true,
        ],

        // Канал доставки писем (Ф2): постановка в очередь, отправка, сбой, прочтение — без ПДн.
        'mail_delivery' => [
            'driver' => 'daily',
            'path' => storage_path('logs/mail_delivery.log'),
            'level' => 'debug',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            // Сбой записи лога не должен ронять отправку письма (job).
            'ignore_exceptions' => true,
        ],

        // Канал доставки билетов в Baza (AF-4): постановка в очередь, запись, сбой, ретрай — без ПДн.
        'baza_delivery' => [
            'driver' => 'daily',
            'path' => storage_path('logs/baza_delivery.log'),
            'level' => 'debug',
            'days' => 30,
            'formatter' => \Monolog\Formatter\JsonFormatter::class,
            // Сбой записи лога не должен ронять доставку билета в Baza (job).
            'ignore_exceptions' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'sentry_logs' => [
            'driver' => 'sentry_logs',
            // The minimum logging level at which this handler will be triggered
            // Available levels: debug, info, notice, warning, error, critical, alert, emergency
            'level' => env('LOG_LEVEL', 'info'), // defaults to `debug` if not set
        ],
    ],

];
