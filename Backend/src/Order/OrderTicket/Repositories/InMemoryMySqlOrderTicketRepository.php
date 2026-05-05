<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Festival\TicketTypesModel;
use App\Models\Festival\TypesOfPaymentModel;
use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Questionnaire\QuestionnaireModel;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Nette\Utils\JsonException;
use Shared\Domain\Criteria\Filter;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForFriendlyListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemForListsResponse;
use Tickets\Order\OrderTicket\Responses\OrderTicketItemResponse;

class InMemoryMySqlOrderTicketRepository implements OrderTicketRepositoryInterface
{
    public function __construct(
        private OrderTicketModel $model,
    )
    {
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
                        'created_at' => (string)(new Carbon()),
                        'updated_at' => (string)(new Carbon()),
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
     * @param Uuid $userId
     *
     * @return OrderTicketItemForListResponse[]
     * @throws JsonException
     */
    public function getUserList(Uuid $userId): array
    {
        $rawData = $this->model::whereUserId($userId->value())
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id')
            ->leftJoin(User::TABLE, $this->model::TABLE . '.user_id',
                '=',
                User::TABLE . '.id'
            )
            ->leftJoin(TypesOfPaymentModel::TABLE, $this->model::TABLE . '.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE . '.id'
            )
            ->select([
                $this->model::TABLE . '.*',
                User::TABLE . '.email',
                User::TABLE . '.city',
                User::TABLE . '.phone',
                TicketTypesModel::TABLE . '.name',
                TypesOfPaymentModel::TABLE . '.name as payment_name',
            ])
            ->selectSub($this->getSubQueryLastComment(), 'last_comment')
            ->selectSub($this->getSubQueryCountQuestionnaire(), 'questionnaire_count')
            ->orderBy($this->model::TABLE . '.kilter')
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
            ->whereColumn('order_tickets_id', $this->model::TABLE . '.id')
            ->latest()
            ->limit(1)
            ->getQuery();
    }

    /**
     * Добавить под запрос на кол-во заполненных анкет
     *
     * @return Builder
     */
    private function getSubQueryCountQuestionnaire(): Builder
    {
        return QuestionnaireModel::select(DB::raw('count(*)'))
            ->whereColumn('order_id', $this->model::TABLE . '.id')
            ->groupBy(QuestionnaireModel::TABLE.'.order_id')
            ->getQuery();
    }

    /**
     * @throws JsonException
     */
    public function findOrder(Uuid $uuid): ?OrderTicketDto
    {
        /** @var OrderTicketModel $rawData */
        $rawData = $this->model::whereId($uuid->value())
            ->with([
                'users',
                'ticketType',
                'location',
            ])
            ->first();

        if (!$rawData) {
            return null;
        }

        $rawDataArr = $rawData->toArray();
        $rawDataArr['email'] = $rawDataArr['users']['email'] ?? '';

        // Заказ-список: нет ticket_type/types_of_payment/price, есть location/curator
        if (!empty($rawDataArr['curator_id'])) {
            return OrderTicketDto::fromStateForList(
                $rawDataArr,
                new Uuid($rawData['users']['id']),
                new Uuid($rawDataArr['curator_id']),
                new Uuid($rawDataArr['location_id']),
                $rawDataArr['project'] ?? null,
            );
        }

        $rawDataArr['questionnaire_type_id'] = $rawData->ticketType->questionnaire_type_id ?? null;
        $guests = json_decode($rawDataArr['guests'], true) ?? [0 => ''];
        return OrderTicketDto::fromState(
            $rawDataArr,
            new Uuid($rawData['users']['id']),
            new PriceDto(
                (int) ((float) $rawData['price'] / max(count($guests), 1)),
                count($guests),
                (float) $rawData['discount'],
            ),
            (bool) ($rawData['ticketType']['is_live_ticket'] ?? false),
        );
    }

    /**
     * @return OrderTicketItemForListResponse[]
     * @throws JsonException
     */
    public function getList(Filters $filters): array
    {
        /** @var Builder $builder */
        $builder = $this->model::leftJoin(
            User::TABLE, $this->model::TABLE . '.user_id',
            '=',
            User::TABLE . '.id')
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id')
            ->leftJoin(TicketTypeFestivalModel::TABLE, TicketTypesModel::TABLE . '.id',
                '=',
                TicketTypeFestivalModel::TABLE . '.ticket_type_id')
            ->leftJoin(FestivalModel::TABLE, TicketTypeFestivalModel::TABLE . '.festival_id',
                '=',
                FestivalModel::TABLE . '.id')
            ->leftJoin(TypesOfPaymentModel::TABLE, $this->model::TABLE . '.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.*',
                User::TABLE . '.email',
                User::TABLE . '.city',
                User::TABLE . '.phone',
                TicketTypesModel::TABLE . '.name',
                TypesOfPaymentModel::TABLE . '.name as payment_name'
            ])
            ->selectSub($this->getSubQueryLastComment(), 'last_comment')
            ->whereNull($this->model::TABLE . '.friendly_id')
            ->whereNull($this->model::TABLE . '.curator_id')
            ->selectSub($this->getSubQueryCountQuestionnaire(), 'questionnaire_count')
            ->orderBy($this->model::TABLE . '.kilter', 'DESC');

        $rawData = FilterBuilder::build($builder, $filters)
            ->get()
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
    public function changeStatus(Uuid $orderId, Status $newStatus, array $guests): bool
    {

        $arrGuests = [];
        foreach ($guests as $guest) {
            $arrGuests[] = [
                'value' => $guest->getValue(),
                'id' => $guest->getId()->value(),
                'email' => $guest->getEmail(),
            ];
        }
        DB::beginTransaction();
        try {
            $order = $this->model::find($orderId->value());

            // Optimistic concurrency: проверяем что статус не был изменён другим запросом
            if ($order->status === (string)$newStatus) {
                DB::commit();
                return true; // Уже в целевом статусе — идемпотентность
            }

            $order->status = (string)$newStatus;
            $order->guests = $arrGuests;
            $order->save();
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param Uuid $orderId
     * @param array $guests
     * @return bool
     * @throws Throwable
     */
    public function updateGuests(Uuid $orderId, array $guests): bool
    {
        $arrGuests = [];
        foreach ($guests as $guest) {
            $arrGuests[] = [
                'value' => $guest->getValue(),
                'id' => $guest->getId()->value(),
                'email' => $guest->getEmail(),
                'number' => $guest->getNumber(),
                'festival_id' => $guest->getFestivalId()?->value(),
            ];
        }

        DB::beginTransaction();
        try {
            $order = $this->model::find($orderId->value());
            $order->guests = $arrGuests;
            $order->save();
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param Uuid $orderId
     * @param float $newPrice
     * @return bool
     * @throws Throwable
     */
    public function changePrice(Uuid $orderId, float $newPrice): bool
    {
        DB::beginTransaction();
        try {
            $order = $this->model::find($orderId->value());
            $order->price = $newPrice;
            $order->discount = 0; // Сбрасываем скидку при ручном изменении цены
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
            ->with('location')
            ->first()
            ?->toArray();

        return is_null($rawData) ? null : OrderTicketItemResponse::fromState($rawData);
    }

    /**
     * @throws JsonException
     */
    public function getFriendlyList(Filters $filters): array
    {
        /** @var Builder $builder */
        $builder = $this->model
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE . '.ticket_type_id',
                '=',
                TicketTypesModel::TABLE . '.id')
            ->leftJoin(TicketTypeFestivalModel::TABLE, TicketTypesModel::TABLE . '.id',
                '=',
                TicketTypeFestivalModel::TABLE . '.ticket_type_id')
            ->leftJoin(FestivalModel::TABLE, TicketTypeFestivalModel::TABLE . '.festival_id',
                '=',
                FestivalModel::TABLE . '.id')
            ->leftJoin(TypesOfPaymentModel::TABLE, $this->model::TABLE . '.types_of_payment_id',
                '=',
                TypesOfPaymentModel::TABLE . '.id')
            ->leftJoin(User::TABLE, $this->model::TABLE . '.friendly_id',
                '=',
                User::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.*',
                TicketTypesModel::TABLE . '.name',
                User::TABLE . '.name as pusher_name',
                User::TABLE . '.email as pusher_email',
            ])
            ->whereNotNull($this->model::TABLE . '.friendly_id')
            ->whereNull($this->model::TABLE . '.curator_id')
            ->selectSub($this->getSubQueryCountQuestionnaire(), 'questionnaire_count')
            ->orderBy($this->model::TABLE . '.kilter', 'DESC');

        $rawData = FilterBuilder::build($builder, $filters)
            ->get()
            ->toArray();

        $result = [];

        foreach ($rawData as $datum) {
            $result[] = OrderTicketItemForFriendlyListResponse::fromState($datum);
        }

        return $result;
    }

    /**
     * Список заказов-списков для admin / manager.
     * Фильтр: WHERE curator_id IS NOT NULL.
     *
     * @return OrderTicketItemForListsResponse[]
     * @throws JsonException
     */
    public function getListsList(Filters $filters): array
    {
        /** @var Builder $builder */
        $builder = $this->model
            ->leftJoin(User::TABLE, $this->model::TABLE . '.user_id',
                '=',
                User::TABLE . '.id')
            ->leftJoin(\App\Models\Location\LocationModel::TABLE, $this->model::TABLE . '.location_id',
                '=',
                \App\Models\Location\LocationModel::TABLE . '.id')
            ->leftJoin(FestivalModel::TABLE, $this->model::TABLE . '.festival_id',
                '=',
                FestivalModel::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.*',
                User::TABLE . '.email',
                User::TABLE . '.city',
                User::TABLE . '.phone',
                \App\Models\Location\LocationModel::TABLE . '.name as location_name',
                FestivalModel::TABLE . '.name as festival_name',
            ])
            ->selectSub($this->getCuratorNameSubQuery(),  'curator_name')
            ->selectSub($this->getCuratorEmailSubQuery(), 'curator_email')
            ->selectSub($this->getSubQueryCountQuestionnaire(), 'questionnaire_count')
            ->whereNotNull($this->model::TABLE . '.curator_id')
            ->orderBy($this->model::TABLE . '.kilter', 'DESC');

        $rawData = FilterBuilder::build($builder, $filters)
            ->get()
            ->toArray();

        $result = [];
        foreach ($rawData as $datum) {
            $result[] = OrderTicketItemForListsResponse::fromState($datum);
        }

        return $result;
    }

    /**
     * Список заказов-списков для конкретного куратора.
     * Фильтр: WHERE curator_id = :curatorId.
     *
     * @return OrderTicketItemForListsResponse[]
     * @throws JsonException
     */
    public function getCuratorList(Filters $filters): array
    {
        return $this->getListsList($filters);
    }

    private function getCuratorNameSubQuery(): Builder
    {
        return User::query()->select('name')
            ->whereColumn('id', $this->model::TABLE . '.curator_id')
            ->limit(1)
            ->getQuery();
    }

    private function getCuratorEmailSubQuery(): Builder
    {
        return User::query()->select('email')
            ->whereColumn('id', $this->model::TABLE . '.curator_id')
            ->limit(1)
            ->getQuery();
    }
}
