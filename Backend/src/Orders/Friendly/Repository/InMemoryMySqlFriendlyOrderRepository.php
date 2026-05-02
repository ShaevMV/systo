<?php

declare(strict_types=1);

namespace Tickets\Orders\Friendly\Repository;

use App\Models\Festival\TicketTypesModel;
use App\Models\Ordering\FriendlyOrderModel;
use App\Models\User;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Friendly\Domain\FriendlyOrder;
use Tickets\Orders\Friendly\Dto\FriendlyOrderDto;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

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

    public function getItem(Uuid $id): ?OrderItemResponse
    {
        $row = $this->model::where(FriendlyOrderModel::TABLE . '.id', $id->value())
            ->leftJoin(
                TicketTypesModel::TABLE,
                FriendlyOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                FriendlyOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->select([
                FriendlyOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return OrderItemResponse::fromRow($row->toArray(), 'friendly');
    }

    public function getUserList(Uuid $userId): array
    {
        $rows = $this->model::where(FriendlyOrderModel::TABLE . '.user_id', $userId->value())
            ->leftJoin(
                TicketTypesModel::TABLE,
                FriendlyOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                FriendlyOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->select([
                FriendlyOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
            ])
            ->orderBy(FriendlyOrderModel::TABLE . '.kilter', 'desc')
            ->get();

        return $rows
            ->map(fn($row) => OrderListItemResponse::fromRow($row->toArray(), 'friendly'))
            ->all();
    }

    public function getList(?string $status = null, ?Uuid $festivalId = null): array
    {
        $query = $this->model::leftJoin(
                TicketTypesModel::TABLE,
                FriendlyOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                FriendlyOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->select([
                FriendlyOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
            ])
            ->orderBy(FriendlyOrderModel::TABLE . '.kilter', 'desc');

        if ($status !== null) {
            $query->where(FriendlyOrderModel::TABLE . '.status', $status);
        }

        if ($festivalId !== null) {
            $query->where(FriendlyOrderModel::TABLE . '.festival_id', $festivalId->value());
        }

        return $query
            ->get()
            ->map(fn($row) => OrderListItemResponse::fromRow($row->toArray(), 'friendly'))
            ->all();
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
