<?php
declare(strict_types=1);

namespace Tickets\PromoCode\Repositories;

use App\Models\Festival\FestivalModel;
use App\Models\Ordering\InfoForOrder\PromoCodeModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\OrderTicketModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Nette\Utils\JsonException;
use Throwable;
use Tickets\PromoCode\Response\PromoCodeDto;
use Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlPromoCode implements PromoCodeInterface
{
    public function __construct(
        private PromoCodeModel $model,
    )
    {
    }


    public function find(string $name, Uuid $ticketTypeId, Uuid $festivalId): ?PromoCodeDto
    {
        $promoCode = $this->model->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE . '.name',
            '=',
            OrderTicketModel::TABLE . '.promo_code')
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE . '.ticket_type_id', '=', TicketTypesModel::TABLE. '.id')
            ->leftJoin(FestivalModel::TABLE, $this->model::TABLE . '.festival_id','=', FestivalModel::TABLE. '.id')
            ->where(function ($query) use ($ticketTypeId){
                $query->where($this->model::TABLE . '.ticket_type_id', '=', $ticketTypeId->value())
                    ->orWhereNull($this->model::TABLE . '.ticket_type_id', null);
            })
            ->where(function ($query) use ($festivalId){
                $query->where($this->model::TABLE . '.festival_id', '=', $festivalId->value())
                    ->orWhereNull($this->model::TABLE . '.festival_id', null);
            })
            ->where($this->model::TABLE . '.name', '=', $name)
            ->where($this->model::TABLE . '.active', '=', true)
            ->select([
                $this->model::TABLE . '.*',
                TicketTypesModel::TABLE. '.name as ticket_type_name',
                \DB::raw('count(' . OrderTicketModel::TABLE . '.id) AS countUses'),
                FestivalModel::TABLE. '.name as festival_name',
                FestivalModel::TABLE. '.year as festival_year',
            ])
            ->groupBy([
                OrderTicketModel::TABLE . '.promo_code',
                $this->model::TABLE . '.id',
                $this->model::TABLE . '.name',
                $this->model::TABLE . '.discount',
                $this->model::TABLE . '.is_percent',
                $this->model::TABLE . '.active',
                $this->model::TABLE . '.limit',
                TicketTypesModel::TABLE . '.name',
            ])
            ->first()?->toArray();

        if (!is_null($promoCode)) {
            return PromoCodeDto::fromState($promoCode);
        }

        return null;
    }

    public function getList(): array
    {
        $dataRaws = $this->model
            ->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE . '.name',
                '=',
                OrderTicketModel::TABLE . '.promo_code')
            ->leftJoin(TicketTypesModel::TABLE, $this->model::TABLE . '.ticket_type_id', '=', TicketTypesModel::TABLE. '.id')
            ->leftJoin(FestivalModel::TABLE, $this->model::TABLE . '.festival_id','=', FestivalModel::TABLE. '.id')
            ->select([
                $this->model::TABLE . '.*',
                \DB::raw('count(' . OrderTicketModel::TABLE . '.id) AS countUses'),
                TicketTypesModel::TABLE. '.name as ticket_type_name',
                FestivalModel::TABLE. '.name as festival_name',
                FestivalModel::TABLE. '.year as festival_year',
            ])
            ->groupBy([
                OrderTicketModel::TABLE . '.promo_code',
                $this->model::TABLE . '.id',
                $this->model::TABLE . '.name',
                $this->model::TABLE . '.discount',
                $this->model::TABLE . '.is_percent',
                $this->model::TABLE . '.active',
                $this->model::TABLE . '.limit',
                TicketTypesModel::TABLE. '.name',
            ])
            ->orderBy($this->model::TABLE . '.updated_at','desc')
            ->get()?->toArray();

        $result = [];
        if (is_null($dataRaws)) {
            return $result;
        }

        foreach ($dataRaws as $dataRaw) {
            $result[$dataRaw['id']] = PromoCodeDto::fromState($dataRaw);
        }

        return $result;
    }

    public function getItem(Uuid $id): ?PromoCodeDto
    {
        $data = $this->model::find($id->value())?->toArray();

        return is_null($data) ? null : PromoCodeDto::fromState($data);
    }

    /**
     * @throws Throwable
     * @throws JsonException
     */
    public function createOrUpdate(PromoCodeDto $promoCodeDto): bool
    {

        try {
            DB::beginTransaction();
            $rawModel = $this->model::whereId($promoCodeDto->getId()->value());
            if (!$rawModel->exists()) {
                $data = $promoCodeDto->toArrayForTable();
                $this->model->insert(
                    array_merge($data,
                        [
                            'created_at' => (string)(new Carbon()),
                            'updated_at' => (string)(new Carbon()),
                        ]
                    ));
            } else {
                $rawModel
                    ->update($promoCodeDto->toArrayForTable());
            }
            DB::commit();
            return true;
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }
    }
}
