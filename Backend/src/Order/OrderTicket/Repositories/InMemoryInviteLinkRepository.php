<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Invite\InviteModel;
use App\Models\Ordering\OrderTicketModel;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderStatusListDto;

class InMemoryInviteLinkRepository implements InviteLinkRepositoryInterface
{
    public function __construct(
        private OrderTicketModel $orderTicketModel,
        private InviteModel      $model,
    )
    {
    }

    public function isPaidOrderByUserId(Uuid $userId): bool
    {
        return $this->orderTicketModel::where([
            'user_id' => $userId->value(),
            'status' => Status::PAID,
        ])->exists();
    }

    /**
     * @throws Throwable
     */
    public function addOrderInInviteLink(Uuid $id, Uuid $orderId): void
    {
        $orderList = $this->model::find($id->value())?->first()?->order_id_list ?? [];

        if (!is_array($orderList)) {
            $orderList = json_decode($orderList, true);
        }
        $orderList[] = $orderId->value();

        try {
            DB::beginTransaction();
            $rawModel = $this->model::whereId($id->value());
            if (!$rawModel->exists()) {
                $this->model::create([
                    'id' => $id->value(),
                    'order_id_list' => json_encode($orderList),
                ]);
            } else {
                $rawModel
                    ->update([
                        'order_id_list' => json_encode($orderList),
                    ]);
            }
            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }

    public function getOrderStatusListInInviteLink(Uuid $id): OrderStatusListDto
    {
        $orderList = $this->model::find($id->value())?->first()?->order_id_list ?? [];
        if(empty($orderList)) {
            return new OrderStatusListDto();
        }
        if (!is_array($orderList)) {
            $orderList = json_decode($orderList, true);
        }
        $countNew = 0;
        $countPaid = 0;
        $countCancel = 0;
        /** @var OrderTicketModel $item */
        foreach ($this->orderTicketModel::whereIn('id', $orderList)->get() as $item) {
            if($item->status === Status::PAID) {
                $countPaid++;
            }

            if($item->status === Status::NEW) {
                $countNew++;
            }


            if($item->status === Status::CANCEL || $item->status === Status::DIFFICULTIES_AROSE) {
                $countCancel++;
            }
        }

        return new OrderStatusListDto(
            $countNew,
            $countPaid,
            $countCancel
        );

    }
}
