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

    public function getByOrderId(Uuid $orderId): ?QuestionnaireGetItemQueryResponse
    {
        $result = $this->model::whereOrderId($orderId->value())->first();

        if($result === null) {
            return null;
        }

        return new QuestionnaireGetItemQueryResponse(
            $result->id,
            $result->order_id,
            $result->agy,
            $result->howManyTimes,
            $result->questionForSysto,
            $result->telegram,
            $result->vk,
            $result->musicStyles
        );
    }
}
