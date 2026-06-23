<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Tickets\QrOrder\Application\Consumer\QrHandleOutcome;
use Tickets\QrOrder\Application\Consumer\QrInboundMessageHandler;

/**
 * Долгоживущий консьюмер RabbitMQ: забирает заказы от витрины qr из vhost qr-integration
 * (очереди q.qr.order / q.qr.email) и проводит их через ТЕ ЖЕ точки приёма, что HTTP
 * (QrInboundMessageHandler → QrOrderApplication / QrEmailIntake). Ручной ack; транзиентный
 * сбой — повтор до предела попыток по x-delivery-count (quorum-очередь), затем в DLQ; битый
 * контракт — сразу в DLQ (reject → DLX-политика → q.qr.dlq). Реконнект на разрыве.
 *
 * Канал по умолчанию ВЫКЛЮЧЕН (нет services.qr_broker.host) — команда сразу выходит
 * (безопасно на проде, где брокера пока нет). Под supervisord подключим отдельной фазой.
 */
class QrConsumeCommand extends Command
{
    protected $signature = 'qr:consume';

    protected $description = 'Консьюмер RabbitMQ: приём заказов qr→org из vhost qr-integration';

    /** @var list<string> */
    private const QUEUES = ['q.qr.order', 'q.qr.email'];

    public function handle(QrInboundMessageHandler $handler): int
    {
        /** @var array<string, mixed> $cfg */
        $cfg = (array) config('services.qr_broker');
        $log = logger()->channel('qr_consumer');

        if (empty($cfg['host'])) {
            $this->warn('qr_broker не настроен (нет RABBITMQ_HOST) — канал выключен, выхожу.');
            $log->info('qr-consume: канал выключен (нет host)');

            return self::SUCCESS;
        }

        $maxAttempts = max(1, (int) ($cfg['max_attempts'] ?? 5));

        // Петля реконнекта: на разрыве соединения переоткрываем и продолжаем.
        while (true) {
            try {
                $connection = $this->connect($cfg);
                $channel = $connection->channel();
                $channel->basic_qos(0, max(1, (int) ($cfg['prefetch'] ?? 1)), false);

                $callback = fn (AMQPMessage $msg) => $this->onMessage($handler, $log, $maxAttempts, $msg);
                foreach (self::QUEUES as $queue) {
                    $channel->basic_consume($queue, '', false, false, false, false, $callback);
                }

                $this->info('qr-consume: подключён, слушаю '.implode(', ', self::QUEUES).' (Ctrl-C для остановки)');
                $log->info('qr-consume: старт', ['queues' => self::QUEUES]);

                while ($channel->is_consuming()) {
                    $channel->wait();
                }

                $channel->close();
                $connection->close();

                return self::SUCCESS;
            } catch (AMQPConnectionClosedException|AMQPRuntimeException|\ErrorException $e) {
                $log->warning('qr-consume: разрыв соединения, реконнект через 5с', ['error' => $e->getMessage()]);
                $this->warn('Разрыв: '.$e->getMessage().' — реконнект через 5с');
                sleep(5);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $cfg
     */
    private function connect(array $cfg): AbstractConnection
    {
        $host = (string) $cfg['host'];
        $port = (int) ($cfg['port'] ?? 5672);
        $vhost = (string) ($cfg['vhost'] ?? 'qr-integration');
        $user = (string) ($cfg['user'] ?? 'qr_consumer');
        $pass = (string) ($cfg['password'] ?? '');
        // heartbeat>0 + read_write_timeout=2*heartbeat → авто-детект разрыва на idle-консьюмере.
        $hb = max(10, (int) ($cfg['heartbeat'] ?? 30));

        if (! empty($cfg['tls'])) {
            // mTLS (Ф3) — для внешнего канала qr. Внутренний консьюмер обычно без TLS.
            $ssl = array_filter([
                'cafile' => $cfg['cacert'] ?? null,
                'local_cert' => $cfg['cert'] ?? null,
                'local_pk' => $cfg['key'] ?? null,
                'verify_peer' => true,
            ], static fn ($v) => $v !== null);

            return new AMQPSSLConnection($host, $port, $user, $pass, $vhost, $ssl, [
                'heartbeat' => $hb,
                'read_write_timeout' => $hb * 2,
                'connection_timeout' => 5.0,
            ]);
        }

        return new AMQPStreamConnection(
            $host, $port, $user, $pass, $vhost,
            false, 'AMQPLAIN', null, 'en_US',
            5.0, $hb * 2, null, false, $hb,
        );
    }

    private function onMessage(QrInboundMessageHandler $handler, LoggerInterface $log, int $maxAttempts, AMQPMessage $msg): void
    {
        $type = (string) $msg->getRoutingKey();
        $body = json_decode($msg->getBody(), true);

        if (! is_array($body)) {
            // Нечитаемое тело — в DLQ, не ретраить.
            $log->warning('qr-consume: тело не JSON → DLQ', ['type' => $type]);
            $msg->reject(false);

            return;
        }

        $outcome = $handler->handle($type, $body);

        switch ($outcome) {
            case QrHandleOutcome::Ack:
                $msg->ack();
                break;

            case QrHandleOutcome::Dlq:
                // requeue=false → срабатывает DLX-политика → x.qr.dlx → q.qr.dlq.
                $msg->reject(false);
                break;

            case QrHandleOutcome::Retry:
                $this->retryOrDlq($log, $maxAttempts, $type, $msg);
                break;
        }
    }

    private function retryOrDlq(LoggerInterface $log, int $maxAttempts, string $type, AMQPMessage $msg): void
    {
        $deliveries = $this->deliveryCount($msg);
        if ($deliveries >= $maxAttempts) {
            $log->warning('qr-consume: лимит попыток исчерпан → DLQ', [
                'type' => $type, 'deliveries' => $deliveries, 'max' => $maxAttempts,
            ]);
            $msg->reject(false); // в DLQ
        } else {
            $msg->nack(true); // requeue → повтор (quorum инкрементит x-delivery-count)
        }
    }

    /**
     * Число доставок: quorum-очередь ведёт x-delivery-count в заголовках (растёт на каждый requeue).
     */
    private function deliveryCount(AMQPMessage $msg): int
    {
        try {
            $headers = $msg->get('application_headers');
            $data = $headers instanceof AMQPTable ? $headers->getNativeData() : [];
            $count = (int) ($data['x-delivery-count'] ?? 0);

            return $count + ($msg->isRedelivered() ? 1 : 0);
        } catch (\Throwable) {
            return $msg->isRedelivered() ? 1 : 0;
        }
    }
}
