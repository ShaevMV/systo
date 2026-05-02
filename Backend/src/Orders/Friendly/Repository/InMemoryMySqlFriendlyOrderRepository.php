<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Repository;

use App\Models\Ordering\FriendlyOrderModel;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;

final class InMemoryMySqlFriendlyOrderRepository implements FriendlyOrderRepositoryInterface
{
    public function __construct(private readonly FriendlyOrderModel $model) {}

    public function create(FriendlyOrderDto $dto): int
    {
        $row = $this->model->create([
            'id'             => $dto->getId()->value(),
            'festival_id'    => $dto->getFestivalId()->value(),
            'user_id'        => $dto->getPusherId()->value(),
            'ticket_type_id' => $dto->getTicketTypeId()->value(),
            'ticket'         => array_map(fn(GuestsDto $g) => $g->toArray(), $dto->getTickets()),
            'status'         => Status::PAID,
            'price'          => $dto->getPriceDto()->getTotalPrice(),
        ]);

        return (int)$row->kilter;
    }

    public function findById(Uuid $id): ?FriendlyOrder
    {
        $row = $this->model::find($id->value());
        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(FriendlyOrder $order): void
    {
        $this->model::where('id', $order->getId()->value())->update([
            'status' => (string)$order->getStatus(),
            'ticket' => array_map(fn(GuestsDto $g) => $g->toArray(), $order->getTickets()),
        ]);
    }

    private function hydrate(FriendlyOrderModel $row): FriendlyOrder
    {
        $tickets = array_map(
            fn(array $data) => GuestsDto::fromState($data),
            $row->ticket,
        );

        return new FriendlyOrder(
            id:           new Uuid($row->id),
            festivalId:   new Uuid($row->festival_id),
            userId:       new Uuid($row->user_id),
            status:       new Status($row->status),
            tickets:      $tickets,
            ticketTypeId: new Uuid($row->ticket_type_id),
            price:        new PriceDto((int)$row->price, 1, 0),
        );
    }
}
