<?php

namespace Tickets\Order\OrderFriendly\Domain;

use Nette\Utils\Json;
use Tickets\Order\Shared\Domain\BaseOrderTicketDto;
use Tickets\Order\Shared\Dto\GuestsDto;
use Tickets\Order\Shared\Dto\PriceDto;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class OrderTicketDto extends BaseOrderTicketDto
{

    public static function fromState(array $data,
                                     Uuid $userId,
                                     PriceDto $priceDto): BaseOrderTicketDto
    {
        $id = isset($data['id']) ? new Uuid($data['id']) : null;
        $status = $data['status'] ?? Status::PAID;
        $guests = is_array($data['guests']) ? $data['guests'] : Json::decode($data['guests'], 1);
        $tickets = [];
        foreach ($guests as $guest) {
            $tickets[] = GuestsDto::fromState($guest);
        }

        return new self(
            new Uuid($data['festival_id']),
            $userId,
            $data['email'],
            $tickets,
            $priceDto,
            new Status($status),
            $id
        );
    }

    public function toArray(): array
    {
        $tickets = [];
        foreach ($this->ticket as $item) {
            $tickets[] = [
                'value' => $item->getValue(),
                'id' => $item->getId()->value(),
            ];
        }

        return [
            'id' => $this->id,
            'festival_id' => $this->festival_id,
            'user_id' => $this->user_id,
            'guests' => Json::encode($tickets),
            'price' => $this->priceDto->getPrice(),
            'status' => (string)$this->status,
        ];
    }
}
