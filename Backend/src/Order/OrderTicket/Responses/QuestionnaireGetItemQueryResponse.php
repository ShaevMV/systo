<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;

class QuestionnaireGetItemQueryResponse extends AbstractionEntity implements Response
{
    public function __construct(
        protected int $id,
        protected string $order_id,
        protected int $agy,
        protected int $howManyTimes,
        protected string $questionForSysto,
        protected ?string $telegram = null,
        protected ?string $vk = null,
        protected ?string $musicStyles = null,
    )
    {
    }
}
