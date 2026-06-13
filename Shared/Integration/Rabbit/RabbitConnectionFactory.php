<?php

declare(strict_types=1);

namespace Shared\Integration\Rabbit;

use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * RabbitConnectionFactory — фабрика AMQP-соединения (php-amqplib, чистый PHP, без PECL).
 *
 * Прототип шины qr ↔ org ↔ BAZA. **RabbitMQ вынесен на ОТДЕЛЬНЫЙ сервер**, поэтому:
 * - host/порт/креды — всегда из конфига приложения (env), не хардкод;
 * - для межсерверного трафика (несёт ПДн + команды на выпуск билета) поддержан **TLS (AMQPS)**:
 *   если `ssl.enabled = true` — используется {@see AMQPSSLConnection} с проверкой сертификата.
 *
 * Локальный docker-контейнер `rabbitmq` — только для дев/прототипа (plain, без TLS).
 * В staging/prod RABBITMQ_HOST указывает на выделенный сервер, RABBITMQ_SSL=true.
 *
 * Параметры передаются явно — Shared не зависит от Laravel config() (Dependency Rule).
 */
final class RabbitConnectionFactory
{
    /**
     * @param array<string, mixed> $sslOptions опции TLS (cafile, local_cert, verify_peer ...) — пусто = без TLS
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
        private readonly bool $ssl = false,
        private readonly array $sslOptions = [],
        private readonly float $connectionTimeout = 5.0,
        private readonly float $readWriteTimeout = 10.0,
    ) {
    }

    public function make(): AbstractConnection
    {
        if ($this->ssl) {
            // verify_peer по умолчанию включаем — иначе TLS бессмысленен (MITM).
            $sslOptions = $this->sslOptions + ['verify_peer' => true, 'verify_peer_name' => true];

            return new AMQPSSLConnection(
                host: $this->host,
                port: $this->port,
                user: $this->user,
                password: $this->password,
                vhost: $this->vhost,
                ssl_options: $sslOptions,
                options: [
                    'connection_timeout' => $this->connectionTimeout,
                    'read_write_timeout' => $this->readWriteTimeout,
                    'keepalive' => true,
                    'heartbeat' => (int) ($this->readWriteTimeout / 2),
                ],
            );
        }

        return new AMQPStreamConnection(
            host: $this->host,
            port: $this->port,
            user: $this->user,
            password: $this->password,
            vhost: $this->vhost,
            connection_timeout: $this->connectionTimeout,
            read_write_timeout: $this->readWriteTimeout,
            keepalive: true,
            heartbeat: (int) ($this->readWriteTimeout / 2),
        );
    }

    /**
     * @param array<string, mixed> $config массив host/port/user/password/vhost/ssl/ssl_options
     */
    public static function fromConfig(array $config): self
    {
        return new self(
            host: (string) ($config['host'] ?? 'rabbitmq'),
            port: (int) ($config['port'] ?? 5672),
            user: (string) ($config['user'] ?? 'guest'),
            password: (string) ($config['password'] ?? 'guest'),
            vhost: (string) ($config['vhost'] ?? '/'),
            ssl: (bool) ($config['ssl'] ?? false),
            sslOptions: is_array($config['ssl_options'] ?? null) ? $config['ssl_options'] : [],
        );
    }
}
