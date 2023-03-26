<?php
declare(strict_types=1);

namespace Tickets\PromoCode\Repositories;

use App\Models\Ordering\InfoForOrder\PromoCodeModel;
use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\OrderTicketModel;
use Tickets\PromoCode\Response\PromoCodeDto;

class InMemoryMySqlPromoCode implements PromoCodeInterface
{
    public function __construct(
        private PromoCodeModel $model,
    )
    {
    }


    public function find(string $name): ?PromoCodeDto
    {
        $promoCode = $this->model->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE.'.name',
            '=',
            OrderTicketModel::TABLE.'.promo_code')
            ->where($this->model::TABLE.'.name','=',$name)
            ->where($this->model::TABLE.'.active','=',true)
            ->groupBy([
                OrderTicketModel::TABLE.'.promo_code',
                $this->model::TABLE.'.id',
                $this->model::TABLE.'.name',
                $this->model::TABLE.'.discount',
                $this->model::TABLE.'.is_percent',
                $this->model::TABLE.'.active',
                $this->model::TABLE.'.limit',
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
            ->leftJoin(OrderTicketModel::TABLE, $this->model::TABLE.'.name',
                '=',
                OrderTicketModel::TABLE.'.promo_code')
            ->select([
                $this->model::TABLE.'.*',
                \DB::raw('count('.OrderTicketModel::TABLE.'.id) AS countUses')
            ])
            ->groupBy([
                OrderTicketModel::TABLE.'.promo_code',
                $this->model::TABLE.'.id',
                $this->model::TABLE.'.name',
                $this->model::TABLE.'.discount',
                $this->model::TABLE.'.is_percent',
                $this->model::TABLE.'.active',
                $this->model::TABLE.'.limit',
            ])
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
}
