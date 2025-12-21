<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Ordering\InfoForOrder\TicketTypesModel;
use App\Models\Ordering\OrderTicketModel;
use App\Models\Ordering\QuestionnaireModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;
use Tickets\Order\OrderTicket\Responses\QuestionnaireGetItemQueryResponse;
use Tickets\Order\OrderTicket\Util\TicketUtil;

class InMemoryMySqlQuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function __construct(
        private QuestionnaireModel $model
    )
    {
    }

    public function create(QuestionnaireTicketDto $questionnaireTicketDto): bool
    {
        DB::beginTransaction();
        $data = $questionnaireTicketDto->toArrayForMySql();
        try {
            $rawModel = $this->model::whereOrderId($questionnaireTicketDto->getOrderId()->value())
                ->whereTicketId($questionnaireTicketDto->getTicketId()->value());
            if($rawModel->exists()) {
                $rawModel->update($data);
            } else {
                $this->model->insert(
                    array_merge($data,
                        [
                            'created_at' => (string)(new Carbon()),
                            'updated_at' => (string)(new Carbon()),
                        ]
                    ));
            }
            DB::commit();
            return true;
        } catch (Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function getByOrderId(Uuid $orderId): QuestionnaireGetItemQueryResponse
    {
        $result = [];
        $rawData = $this->model::whereOrderId($orderId->value())
            ->leftJoin(OrderTicketModel::TABLE,
            $this->model::TABLE . '.order_id',
            '=',
            OrderTicketModel::TABLE . '.id')
            ->select([
                $this->model->getTable().'.*',
                OrderTicketModel::TABLE . '.guests'
            ])->get()
            ->toArray();

        foreach ($rawData as $item) {
            $result[] = new QuestionnaireTicketDto(
                new Uuid($item['ticket_id']),
                new Uuid($item['order_id']),
                $item['agy'],
                $item['howManyTimes'],
                $item['questionForSysto'],
                $item['phone'],
                $item['telegram'],
                $item['vk'],
                $item['musicStyles'],
                TicketUtil::findGuestByUuid(
                    new Uuid($item['ticket_id']),
                    json_decode($item['guests'], true),
                )?->getValue() ?? null,

            );
        }

        return new QuestionnaireGetItemQueryResponse($result);
    }
}
