<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;



use Shared\Domain\Bus\Query\QueryBus;
use Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;
use Tickets\Order\OrderTicket\Responses\FestivalListResponse;

class FestivalApplication
{
    private QueryBus $queryBus;

    public function __construct(
        private GetFestivalListQueryHandler $festivalListQueryHandler
    )
    {
        $this->queryBus = new InMemorySymfonyQueryBus([
            GetFestivalListQuery::class => $this->festivalListQueryHandler,
        ]);
    }

    public function getAllFestival(): FestivalListResponse
    {
        /** @var FestivalListResponse $response */
        $response = $this->queryBus->ask(
            new GetFestivalListQuery()
        );

        return $response;
    }
}
