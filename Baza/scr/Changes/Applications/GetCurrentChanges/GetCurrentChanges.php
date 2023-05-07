<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\GetCurrentChanges;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

class GetCurrentChanges
{
    private QueryBus $bus;

    public function __construct(
        GetCurrentChangesQueryHandler $getCurrentChangesQueryHandler
    )
    {
        $this->bus = new InMemorySymfonyQueryBus([
            GetCurrentChangesQuery::class => $getCurrentChangesQueryHandler
        ]);
    }

    public function getId(int $userId): int
    {
        /** @var ChangeIdResponse|null $result */
        $result = $this->bus->ask(new GetCurrentChangesQuery($userId));


        if(is_null($result)) {
            throw new \DomainException('Смена не найдена');
        }

        return $result->getChangeId();
    }
}
