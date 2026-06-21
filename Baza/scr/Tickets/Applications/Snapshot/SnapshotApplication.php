<?php

declare(strict_types=1);

namespace Baza\Tickets\Applications\Snapshot;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Baza\Tickets\Responses\SnapshotPageResponse;

/**
 * Тонкий слой выдачи офлайн-снимка (Ф5, PR-3) — по образцу SearchEngine.
 *
 * Собирает QueryBus с единственным обработчиком и отдаёт порцию снимка контроллеру.
 */
class SnapshotApplication
{
    private QueryBus $bus;

    public function __construct(GetSnapshotQueryHandler $getSnapshotQueryHandler)
    {
        $this->bus = new InMemorySymfonyQueryBus([
            GetSnapshotQuery::class => $getSnapshotQueryHandler,
        ]);
    }

    public function get(GetSnapshotQuery $query): SnapshotPageResponse
    {
        /** @var SnapshotPageResponse $result */
        $result = $this->bus->ask($query);

        return $result;
    }
}
