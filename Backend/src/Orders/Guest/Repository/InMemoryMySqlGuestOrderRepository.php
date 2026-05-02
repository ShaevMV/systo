<?php

declare(strict_types=1);

namespace Tickets\Orders\Guest\Repository;

use App\Models\Festival\TicketTypesModel;
use App\Models\Festival\TypesOfPaymentModel;
use App\Models\Ordering\GuestOrderModel;
use App\Models\User;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Orders\Guest\Domain\GuestOrder;
use Tickets\Orders\Guest\Dto\GuestOrderDto;
use Tickets\Orders\Shared\Response\OrderItemResponse;
use Tickets\Orders\Shared\Response\OrderListItemResponse;

final class InMemoryMySqlGuestOrderRepository implements GuestOrderRepositoryInterface
{
    public function __construct(private readonly GuestOrderModel $model) {}

    public function create(GuestOrderDto $dto): int
    {
        $row = $this->model->create([
            'id'                  => $dto->getId()->value(),
            'festival_id'         => $dto->getFestivalId()->value(),
            'user_id'             => $dto->getUserId()->value(),
            'ticket_type_id'      => $dto->getTicketTypeId()->value(),
            'types_of_payment_id' => $dto->getTypesOfPaymentId()->value(),
            'ticket'              => array_map(fn(GuestsDto $g) => $g->toArray(), $dto->getTickets()),
            'status'              => (string)$dto->getStatus(),
            'price'               => $dto->getPriceDto()->getPrice(),
            'discount'            => $dto->getPriceDto()->getDiscount(),
            'promo_code'          => $dto->getPromoCode(),
            'phone'               => $dto->getPhone(),
        ]);

        return (int)$row->kilter;
    }

    public function findById(Uuid $id): ?GuestOrder
    {
        $row = $this->model::find($id->value());
        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function save(GuestOrder $order): void
    {
        $this->model::where('id', $order->getId()->value())->update([
            'status' => (string)$order->getStatus(),
            'ticket' => array_map(fn(GuestsDto $g) => $g->toArray(), $order->getTickets()),
        ]);
    }

    public function getItem(Uuid $id): ?OrderItemResponse
    {
        $row = $this->model::where(GuestOrderModel::TABLE . '.id', $id->value())
            ->leftJoin(
                TicketTypesModel::TABLE,
                GuestOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                GuestOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->leftJoin(
                TypesOfPaymentModel::TABLE,
                GuestOrderModel::TABLE . '.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE . '.id',
            )
            ->select([
                GuestOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
                TypesOfPaymentModel::TABLE . '.name as payment_name',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return OrderItemResponse::fromRow($row->toArray(), 'guest');
    }

    public function getUserList(Uuid $userId): array
    {
        $rows = $this->model::where(GuestOrderModel::TABLE . '.user_id', $userId->value())
            ->leftJoin(
                TicketTypesModel::TABLE,
                GuestOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                GuestOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->select([
                GuestOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
            ])
            ->orderBy(GuestOrderModel::TABLE . '.kilter', 'desc')
            ->get();

        return $rows
            ->map(fn($row) => OrderListItemResponse::fromRow($row->toArray(), 'guest'))
            ->all();
    }

    public function getList(?string $status = null, ?Uuid $festivalId = null): array
    {
        $query = $this->model::leftJoin(
                TicketTypesModel::TABLE,
                GuestOrderModel::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id',
            )
            ->leftJoin(
                User::TABLE,
                GuestOrderModel::TABLE . '.user_id',
                '=',
                User::TABLE . '.id',
            )
            ->select([
                GuestOrderModel::TABLE . '.*',
                TicketTypesModel::TABLE . '.name as ticket_type_name',
                User::TABLE . '.email as user_email',
            ])
            ->orderBy(GuestOrderModel::TABLE . '.kilter', 'desc');

        if ($status !== null) {
            $query->where(GuestOrderModel::TABLE . '.status', $status);
        }

        if ($festivalId !== null) {
            $query->where(GuestOrderModel::TABLE . '.festival_id', $festivalId->value());
        }

        return $query
            ->get()
            ->map(fn($row) => OrderListItemResponse::fromRow($row->toArray(), 'guest'))
            ->all();
    }

    private function hydrate(GuestOrderModel $row): GuestOrder
    {
        $tickets = array_map(
            fn(array $data) => GuestsDto::fromState($data),
            $row->ticket,
        );

        return new GuestOrder(
            id:               new Uuid($row->id),
            festivalId:       new Uuid($row->festival_id),
            userId:           new Uuid($row->user_id),
            status:           new Status($row->status),
            tickets:          $tickets,
            typesOfPaymentId: new Uuid($row->types_of_payment_id),
            price:            new PriceDto((int)$row->price, 1, (float)$row->discount),
            ticketTypeId:     new Uuid($row->ticket_type_id),
            phone:            $row->phone,
            promoCode:        $row->promo_code,
        );
    }
}
