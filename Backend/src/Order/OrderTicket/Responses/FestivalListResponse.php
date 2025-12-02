<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;


class FestivalListResponse  extends AbstractionEntity implements Response
{

    /**
     * @param FestivalDto[] $festivalDto
     */
    public function __construct(
        protected array $festivalDto
    )
    {
    }

    /**
     * @return FestivalDto[]
     */
    public function getFestivalDto(): array
    {
        return $this->festivalDto;
    }
}
