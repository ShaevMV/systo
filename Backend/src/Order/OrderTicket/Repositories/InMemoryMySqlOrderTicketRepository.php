<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\Order\OrderTicket\Domain\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;
use Tickets\Shared\Domain\Criteria\Filter;
use Tickets\Shared\Domain\Criteria\Filters;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlOrderTicketRepository implements OrderTicketRepositoryInterface
{
    public function __construct(
        private OrderTicketModel $model,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function create(OrderTicketDto $orderTicketDto): bool
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
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param  Uuid  $userId
     *
     * @return OrderTicketItemForListResponse[]
     * @throws JsonException
     */
    public function getUserList(Uuid $userId): array
    {
        $rawData = $this->model::whereUserId($userId->value())
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE.'.ticket_type_id',
                '=',
                TicketTypesModel::TABLE.'.id')
            ->leftJoin(User::TABLE, $this->model::TABLE.'.user_id',
                '=',
                User::TABLE.'.id'
            )
            ->leftJoin(TypesOfPaymentModel::TABLE, $this->model::TABLE.'.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE.'.id'
            )
            ->select([
                $this->model::TABLE.'.*',
                User::TABLE.'.email',
                TicketTypesModel::TABLE.'.name',
                TypesOfPaymentModel::TABLE.'.name as payment_name'
            ])
            ->selectSub($this->getSubQueryLastComment(), 'last_comment')
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
     * Добавить поздзапрос на последний комментарий
     *
     * @return Builder
     */
    private function getSubQueryLastComment(): Builder
    {
        return CommentOrderTicketModel::select('comment')
            ->whereColumn('order_tickets_id', $this->model::TABLE.'.id')
            ->latest()
            ->limit(1)
            ->getQuery();
    }

    /**
     * @throws JsonException
     */
    public function findOrder(Uuid $uuid): ?OrderTicketDto
    {
        $rawData = $this->model::whereId($uuid->value())
            ->with('users')
            ->first()
            ?->toArray();
        $rawData['email'] = $rawData['users']['email'];

        return $rawData !== null ? OrderTicketDto::fromState(
            $rawData,
            new Uuid($rawData['users']['id']),
            new PriceDto($rawData['price'], $rawData['discount'])
        ) : null;
    }

    /**
     * @return OrderTicketItemForListResponse[]
     * @throws JsonException
     */
    public function getList(Filters $filters): array
    {
        $builder = $this->model::leftJoin(
            User::TABLE, $this->model::TABLE.'.user_id',
            '=',
            User::TABLE.'.id')
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE.'.ticket_type_id',
                '=',
                TicketTypesModel::TABLE.'.id')
            ->leftJoin(TypesOfPaymentModel::TABLE, $this->model::TABLE.'.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE.'.id')
            ->select([
                $this->model::TABLE.'.*',
                User::TABLE.'.email',
                TicketTypesModel::TABLE.'.name',
                TypesOfPaymentModel::TABLE.'.name as payment_name'
            ])
            ->selectSub($this->getSubQueryLastComment(), 'last_comment')
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
     * @param Uuid $orderId
     * @param Status $newStatus
     * @param GuestsDto[] $guests
     * @return bool
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

    public function getItem(Uuid $uuid): ?OrderTicketItemResponse
    {
        $rawData = $this->model::whereId($uuid->value())
            ->with('users')
            ->with('comments')
            ->with('ticketType')
            ->with('typeOfPayment')
            ->with('tickets')
            ->first()
            ?->toArray();

        return is_null($rawData) ? null : OrderTicketItemResponse::fromState($rawData);
    }
}
