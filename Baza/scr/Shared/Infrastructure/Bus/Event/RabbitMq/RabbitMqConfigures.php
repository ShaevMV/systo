<?php

declare(strict_types=1);

namespace Baza\Shared\Infrastructure\Bus\Event\RabbitMq;

use AMQPChannelException;
use AMQPConnectionException;
use AMQPExchangeException;
use AMQPQueue;
use AMQPQueueException;
use Baza\Shared\Domain\Bus\Event\DomainEventSubscriber;
use function Lambdish\Phunctional\each;

final class RabbitMqConfigures
{
    private RabbitMqConnection $connection;

    public function __construct(RabbitMqConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws AMQPExchangeException
     * @throws AMQPConnectionException
     * @throws AMQPChannelException
     */
    public function configure(string $exchangeName, DomainEventSubscriber ...$subscribers): void
    {
        /*$retryExchangeName = RabbitMqExchangeNameFormatter::retry($exchangeName);
        $deadLetterExchangeName = RabbitMqExchangeNameFormatter::deadLetter($exchangeName);*/

        $this->declareExchange($exchangeName);
        /*$this->declareExchange($retryExchangeName);
        $this->declareExchange($deadLetterExchangeName);*/

        $this->declareQueues($exchangeName, ...$subscribers);
    }

    /**
     * @throws AMQPExchangeException
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    private function declareExchange(string $exchangeName): void
    {
        $exchange = $this->connection->exchange($exchangeName);
        $exchange->setType(AMQP_EX_TYPE_TOPIC);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();
    }

    private function declareQueues(
        string $exchangeName,
        DomainEventSubscriber ...$subscribers
    ): void {
        each($this->queueDeclarator($exchangeName), $subscribers);
    }

    private function queueDeclarator(
        string $exchangeName,
    ): callable {
        return function (DomainEventSubscriber $subscriber) use (
            $exchangeName,
        ) {
            $queueName = RabbitMqQueueNameFormatter::format(get_class($subscriber));

            $queue = $this->declareQueue($queueName);

            $queue->bind($exchangeName, $queueName);

            foreach ($subscriber::subscribedTo() as $eventClass) {
                $queue->bind($exchangeName, $eventClass::eventName());
            }
        };
    }

    /**
     * @throws AMQPQueueException
     * @throws AMQPChannelException
     * @throws AMQPConnectionException
     */
    private function declareQueue(
        string $name,
        string $deadLetterExchange = null,
        string $deadLetterRoutingKey = null,
        int $messageTtl = null
    ): AMQPQueue {
        $queue = $this->connection->queue($name);

        if (null !== $deadLetterExchange) {
            $queue->setArgument('x-dead-letter-exchange', $deadLetterExchange);
        }

        if (null !== $deadLetterRoutingKey) {
            $queue->setArgument('x-dead-letter-routing-key', $deadLetterRoutingKey);
        }

        if (null !== $messageTtl) {
            $queue->setArgument('x-message-ttl', $messageTtl);
        }

        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

        return $queue;
    }
}
