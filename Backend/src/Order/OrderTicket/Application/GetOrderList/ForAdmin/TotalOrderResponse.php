<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForAdmin;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\Entity\EntityInterface;

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
    )
    {
    }

    public static function fromState(array $data): self
    {
        // TODO: Implement fromState() method.
    }
}
