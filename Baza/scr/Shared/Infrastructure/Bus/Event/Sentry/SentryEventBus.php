<?php

namespace Baza\Shared\Infrastructure\Bus\Event\Sentry;

use Baza\Shared\Domain\Bus\Event\DomainEvent;
use Baza\Shared\Domain\Bus\Event\EventBus;
use Baza\Shared\Domain\Utils;
use Throwable;
use Sentry\State\Hub;
use function Lambdish\Phunctional\each;

class SentryEventBus implements EventBus
{
    private const DATABASE_TIMESTAMP_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private Hub $connection
    ) {
    }

    public function publish(DomainEvent ...$events): void
    {
        each($this->publisher(), $events);
    }

    public function publisher(): callable
    {
        return function (DomainEvent $domainEvent): void {
            $message = [
                'id' => $domainEvent->eventId(),
                'aggregateId' => $domainEvent->aggregateId(),
                'name' => $domainEvent::eventName(),
                'body' => Utils::jsonEncode($domainEvent->toPrimitives()),
                'occurredOn' => Utils::stringToDate($domainEvent->occurredOn())->format(self::DATABASE_TIMESTAMP_FORMAT)
            ];

            $this->connection->captureMessage(Utils::jsonEncode($message));
        };
    }

    public function pushException(Throwable $exception): void
    {
        $this->connection->captureException($exception);
    }

    public function getConnection(): Hub
    {
        return $this->connection;
    }

    public function setExchangeName(string $exchangeName): void
    {
    }
}
