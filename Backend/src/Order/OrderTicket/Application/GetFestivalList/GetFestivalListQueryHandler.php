<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetFestivalList;

use Shared\Domain\Bus\Query\QueryHandler;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Tickets\Order\OrderTicket\Responses\FestivalListResponse;

class GetFestivalListQueryHandler implements QueryHandler
{
    public function __construct(
        private FestivalRepositoryInterface $repository
    )
    {
    }

    public function __invoke(GetFestivalListQuery $query): FestivalListResponse
    {
        return new FestivalListResponse(
            $this->repository->getFestivalList()
        );
    }
}
