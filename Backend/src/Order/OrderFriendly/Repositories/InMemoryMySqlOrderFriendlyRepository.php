<?php

declare(strict_types=1);

namespace Tickets\Order\OrderFriendly\Repositories;

use App\Models\Ordering\OrderFriendlyModel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Order\OrderFriendly\Domain\OrderTicketDto;
use Tickets\Order\OrderFriendly\Responses\OrderTicketItemForListResponse;
use Tickets\Order\OrderFriendly\Responses\OrderTicketItemResponse;
use Tickets\Order\Shared\Domain\BaseOrderTicketDto;
use Tickets\Order\Shared\Dto\PriceDto;
use Tickets\Order\Shared\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\Shared\Responses\BaseOrderTicketItemResponse;
use Tickets\Shared\Domain\Criteria\Filter;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlOrderFriendlyRepository implements OrderTicketRepositoryInterface
{
    public function __construct(
        private OrderFriendlyModel $model
    ){
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function create(BaseOrderTicketDto $orderTicketDto): bool
    {
        DB::beginTransaction();
        $data = $orderTicketDto->toArray();
        try {
            $this->model->insert(
                array_merge($data,
                    [
                        'created_at' => (string) (new Carbon()),
                        'updated_at' => (string) (new Carbon()),
                    ]
                ));
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function getUserList(Uuid $userId): array
    {
        $rawData = $this->model::whereUserId($userId->value())
            ->leftJoin(User::TABLE, $this->model::TABLE.'.user_id',
                '=',
                User::TABLE.'.id'
            )
            ->select([
                $this->model::TABLE.'.*',
                User::TABLE.'.email',
            ])
            ->orderBy($this->model::TABLE.'.kilter')
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = OrderTicketItemForListResponse::fromState($datum);
        }

        return $result;
    }

    /**
     * @throws JsonException
     */
    public function findOrder(Uuid $uuid): ?BaseOrderTicketDto
    {
        $rawData = $this->model::whereId($uuid->value())
            ->with('users')
            ->first()
            ?->toArray();
        $rawData['email'] = $rawData['users']['email'];

        return $rawData !== null ? OrderTicketDto::fromState(
            $rawData,
            new Uuid($rawData['users']['id']),
            new PriceDto($rawData['price'])
        ) : null;
    }

    public function getItem(Uuid $uuid): ?BaseOrderTicketItemResponse
    {
        $rawData = $this->model::whereId($uuid->value())
            ->with('users')
            ->first()
            ?->toArray();

        return is_null($rawData) ? null : OrderTicketItemResponse::fromState($rawData);
    }

    /**
     * @throws JsonException
     */
    public function getList(Filters $filters): array
    {
        $builder = $this->model::leftJoin(
            User::TABLE, $this->model::TABLE.'.user_id',
            '=',
            User::TABLE.'.id')
            ->select([
                $this->model::TABLE.'.*',
                User::TABLE.'.email',
            ])
            ->orderBy($this->model::TABLE.'.kilter');

        /** @var Filter $filter */
        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->value()->value()
                );
            }
        }
        $rawData = $builder->get()
            ->toArray();

        $result = [];

        foreach ($rawData as $datum) {
            $result[] = OrderTicketItemForListResponse::fromState($datum);
        }

        return $result;
    }

    /**
     * @throws Throwable
     */
    public function chanceStatus(Uuid $orderId, Status $newStatus, array $guests): bool
    {
        DB::beginTransaction();
        $arrGuests = [];
        foreach ($guests as $guest) {
            $arrGuests[]=[
                'value' => $guest->getValue(),
                'id' => $guest->getId()->value(),
            ];
        }

        try {
            $order = $this->model::find($orderId->value());
            $order->status = (string) $newStatus;
            $order->guests = $arrGuests;
            $order->save();
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }
}
