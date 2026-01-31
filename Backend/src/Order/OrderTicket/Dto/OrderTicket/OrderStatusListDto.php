<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

class OrderStatusListDto
{
    public function __construct(
        private int $countNew = 0,
        private int $countPaid = 0,
        private int $countCancel = 0,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'countNew' => $this->countNew,
            'countPaid' => $this->countPaid,
            'countCancel' => $this->countCancel,
        ];
    }
}
