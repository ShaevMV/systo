<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Festival\TicketTypeFestivalModel;
use App\Models\Festival\TicketTypesModel;
use App\Models\Ordering\CommentOrderTicketModel;
use App\Models\Ordering\InfoForOrder\TypesOfPaymentModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Tickets\TicketModel;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use JsonException;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Dto\TicketDto;

class InMemoryMySqlTicketsRepository implements TicketsRepositoryInterface
{
    public function __construct(
        private TicketModel $model,
    )
    {
    }

    /**
     * @throws JsonException
     * @throws Throwable
     */
    public function createTickets(TicketDto $ticketDto): bool
    {

        if ($this->model::whereId($ticketDto->getId())->exists()) {
            return true;
        }
        DB::beginTransaction();
        try {
            $this->model::insert($ticketDto->toArray());
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteTicketsByOrderId(Uuid $orderId): bool
    {
        DB::beginTransaction();
        try {

            $this->model::whereOrderTicketId($orderId->value())->delete();
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * @param Uuid $orderId
     * @param bool $isShowDelete
     * @return Uuid[]
     */
    public function getListIdByOrderId(Uuid $orderId, bool $isShowDelete = false): array
    {
        $result = [];
        if (!$isShowDelete) {
            $model = $this->model;
        } else {
            $model = $this->model::withTrashed();
        }

        $listIds = $model->whereOrderTicketId($orderId->value())
            ->get()
            ->toArray();

        foreach ($listIds as $id) {
            $result[] = new Uuid($id['id']);
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
            ->whereColumn('order_tickets_id', $this->model::TABLE . '.order_ticket_id')
            ->latest()
            ->limit(1)
            ->getQuery();
    }

    public function getTicket(Uuid $ticketId, bool $isShowDelete = false): TicketResponse
    {
        if (!$isShowDelete) {
            $result = $this->model;
        } else {
            $result = $this->model::withTrashed();
        }

        $result = $result->where($this->model::TABLE . '.id', '=', $ticketId->value())
            ->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE . '.order_ticket_id', '=', OrderTicketModel::TABLE . '.id')
            ->leftJoin(User::TABLE, OrderTicketModel::TABLE . '.user_id', '=', User::TABLE . '.id')
            ->leftJoin(User::TABLE . ' as curator_user', OrderTicketModel::TABLE . '.curator_id', '=', 'curator_user.id')
            ->leftJoin(FestivalModel::TABLE, $this->model::TABLE . '.festival_id', '=', FestivalModel::TABLE . '.id')
            ->leftJoin(TicketTypesModel::TABLE, OrderTicketModel::TABLE . '.ticket_type_id', '=', TicketTypesModel::TABLE . '.id')
            ->leftJoin(TicketTypeFestivalModel::TABLE, function ($join) {
                $join->on($this->model::TABLE . '.festival_id', '=', TicketTypeFestivalModel::TABLE . '.festival_id');
                $join->on(OrderTicketModel::TABLE . '.ticket_type_id', '=', TicketTypeFestivalModel::TABLE . '.ticket_type_id');
            })
            ->leftJoin(TypesOfPaymentModel::TABLE, OrderTicketModel::TABLE . '.types_of_payment_id', '=', TypesOfPaymentModel::TABLE . '.id')
            ->select([
                $this->model::TABLE . '.id',
                $this->model::TABLE . '.kilter',
                $this->model::TABLE . '.name',
                $this->model::TABLE . '.deleted_at as ticket_deleted_at',
                TicketTypeFestivalModel::TABLE . '.pdf',
                TicketTypeFestivalModel::TABLE . '.email as emailView',
                TypesOfPaymentModel::TABLE . '.email as emailPayView',
                OrderTicketModel::TABLE . '.phone',
                OrderTicketModel::TABLE . '.status',
                OrderTicketModel::TABLE . '.created_at',
                OrderTicketModel::TABLE . '.ticket_type_id',
                OrderTicketModel::TABLE . '.id as order_id',
                OrderTicketModel::TABLE . '.curator_id',
                OrderTicketModel::TABLE . '.location_id',
                OrderTicketModel::TABLE . '.project',
                'curator_user.email as curator_email',
                'curator_user.name as curator_name',
                $this->model::TABLE . '.festival_id',
                $this->model::TABLE . '.email',
                User::TABLE . '.email as email_user',
                User::TABLE . '.city',
                TicketTypesModel::TABLE . '.name as name_type',
            ])->selectSub($this->getSubQueryLastComment(), 'last_comment');

        \Log::info('Билет ' . $ticketId->value() . ' : ' . $result->toSql());
        $result = $result->first()?->toArray();
        if (is_null($result)) {
            throw new DomainException("Билет {$ticketId->value()} не найден");
        }

        return new TicketResponse(
            $result['name'],
            $result['kilter'],
            new Uuid($result['id']),
            $result['status'],
            empty($result['email']) ? ($result['email_user'] ?? '') : $result['email'],
            $result['phone'] ?? '',
            $result['city'] ?? '',
            $result['last_comment'],
            Carbon::parse($result['created_at']),
            empty($result['pdf']) ? null : $result['pdf'],
            $result['emailPayView'] ?? $result['emailView'],
            new Uuid($result['festival_id']),
            in_array($result['ticket_type_id'], (array) ['222abc0c-fc8e-4a1d-a4b0-d345cafada10']),
            empty($result['ticket_type_id']) ? null : new Uuid($result['ticket_type_id']),
            $result['name_type'] ?? null,
            isset($result['order_id']) ? new Uuid($result['order_id']) : null,
            empty($result['curator_id']) ? null : new Uuid($result['curator_id']),
            $result['curator_email']    ?? null,
            $result['curator_name']     ?? null,
            $result['project']          ?? null,
            empty($result['location_id']) ? null : new Uuid($result['location_id']),
            !empty($result['ticket_deleted_at']),   // isDeleted — soft-deleted билет
        );
    }

    /**
     * @throws \Nette\Utils\JsonException
     */
    public function setInBaza(TicketResponse $ticketsDto): bool
    {
        $data = $ticketsDto->toArrayForBaza();
        try {
            DB::connection('mysqlBaza')->getPdo();
            if (!DB::connection('mysqlBaza')->table('el_tickets')
                ->where('uuid', '=', $ticketsDto->getId()->value())->exists()
            ) {
                return DB::connection('mysqlBaza')
                    ->table('el_tickets')
                    ->insert(
                        $data
                    );
            } else {
                DB::connection('mysqlBaza')->table('el_tickets')
                    ->where('uuid', '=', $ticketsDto->getId()->value())
                    ->update([
                        'status' => $data['status'],
                        'is_need_seedling' => $data['is_need_seedling'],
                        'type_ticket_id' => $data['type_ticket_id'],
                        'type_ticket' => $data['type_ticket'],
                        'name' => $data['name']
                    ]);
            }
        } catch (\Exception $e) {
            return false;
        } finally {
            return true;
        }
    }

    /**
     * Запись/обновление билета-списка в таблицу `spisok_tickets` базы Baza.
     * Используется только для заказов-списков (curator_id IS NOT NULL).
     * Идентификация билета — по `kilter` (наш PK на стороне tickets).
     */
    public function setInBazaList(TicketResponse $ticketsDto): bool
    {
        $data = $ticketsDto->toArrayForSpisok();
        try {
            DB::connection('mysqlBaza')->getPdo();

            $exists = DB::connection('mysqlBaza')->table('spisok_tickets')
                ->where('kilter', '=', $data['kilter'])
                ->exists();

            if (!$exists) {
                return DB::connection('mysqlBaza')
                    ->table('spisok_tickets')
                    ->insert($data);
            }

            DB::connection('mysqlBaza')->table('spisok_tickets')
                ->where('kilter', '=', $data['kilter'])
                ->update([
                    'project'     => $data['project'],
                    'curator'     => $data['curator'],
                    'email'       => $data['email'],
                    'name'        => $data['name'],
                    'comment'     => $data['comment'],
                    'status'      => $data['status'],
                    'festival_id' => $data['festival_id'],
                ]);
        } catch (\Exception $e) {
            Log::error('setInBazaList: ' . $e->getMessage(), ['kilter' => $data['kilter']]);
            return false;
        } finally {
            return true;
        }
    }

    /**
     * @throws \Nette\Utils\JsonException
     */
    public function setInBazaLive(int $number, ?Uuid $ticketId = null): bool
    {
        DB::connection('mysqlBaza')->getPdo();
        if (!DB::connection('mysqlBaza')->table('live_tickets')
            ->where('kilter', '=', $number)->exists()
        ) {
            throw new DomainException('Не найден билет в Базе входа');
        } else {
            return DB::connection('mysqlBaza')->table('live_tickets')
                ->where('kilter', '=', $number)
                ->update([
                    'el_ticket_id' => $ticketId?->value()
                ]) > 0;
        }
    }

    /**
     * @return Uuid[]
     */
    public function getAllTicketsId(?Uuid $festivalId = null): array
    {
        $rawResult = $this->model::withTrashed()
            ->leftJoin('order_tickets', 'order_tickets.id', '=', $this->model::TABLE . '.order_ticket_id');
        if (null !== $festivalId) {
            $rawResult->where('order_tickets.festival_id', '=', $festivalId->value());
        }
        $rawResult = $rawResult->get($this->model::TABLE . '.id')
            ->toArray();

        $result = [];
        foreach ($rawResult as $item) {
            $result[] = new Uuid($item['id']);
        }

        return $result;
    }

    public function checkLiveNumber(int $number): bool
    {
        $res = (array)DB::connection('mysqlBaza')->table('live_tickets')
            ->where('kilter', '=', $number)->first();

        return !empty($res['el_ticket_id']);
    }
}
