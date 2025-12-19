<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Ordering\QuestionnaireModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;
use Tickets\Order\OrderTicket\Responses\QuestionnaireGetItemQueryResponse;

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
        $data = $questionnaireTicketDto->toArray();
        try {
            $rawModel =$this->model::whereOrderId($questionnaireTicketDto->getOrderId()->value())
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
        foreach ($this->model::whereOrderId($orderId->value())->get() as $item) {
            $result[] = new QuestionnaireTicketDto(
                new Uuid($item->ticket_id),
                new Uuid($item->order_id),
                $item->agy,
                $item->howManyTimes,
                $item->questionForSysto,
                $item->telegram,
                $item->vk,
                $item->musicStyles
            );
        }

        return new QuestionnaireGetItemQueryResponse($result);
    }
}
