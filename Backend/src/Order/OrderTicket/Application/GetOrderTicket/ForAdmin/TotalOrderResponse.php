<?php

declare(strict_types = 1);

namespace Tickets\Ordering\OrderTicket\Application\GetOrderTicket\ForAdmin;

use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;

class TotalOrderResponse extends AbstractionEntity implements Response
{
    private const TOTAL_ALL = 'all';
    private const TOTAL_IN_BUY = 'all';
    private const TOTAL_IN_CANCEL = 'all';
    private const TOTAL_IN_ = 'all';

    private const TOTAL_COUNT = [

    ];

    public function __construct(
        protected int $totalCount = 0,
        protected int $total
    )
    {
    }
}
