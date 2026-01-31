<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use App\Models\Ordering\OrderTicketModel;
use App\Models\Questionnaire\QuestionnaireModel;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Util\TicketUtil;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;
use Tickets\Questionnaire\Responses\QuestionnaireGetListQueryResponse;

class InMemoryMySqlQuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function __construct(
        private QuestionnaireModel $model
    )
    {
    }

    public function create(QuestionnaireTicketDto $questionnaireTicketDto): bool
    {

        $data = $questionnaireTicketDto->toArrayForMySql();
        try {
            DB::beginTransaction();
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

    public function getByOrderId(
        Uuid $orderId,
        ?Uuid $ticketId,
    ): QuestionnaireGetListQueryResponse
    {

        $rawData = $this->model::whereOrderId($orderId->value())
            ->leftJoin(OrderTicketModel::TABLE,
            $this->model::TABLE . '.order_id',
            '=',
            OrderTicketModel::TABLE . '.id')
            ->select([
                $this->model->getTable().'.*',
                OrderTicketModel::TABLE . '.guests'
            ]);

        if ($ticketId) {
            $rawData = $rawData->whereTicketId($ticketId->value());
        }

        $result = [];

        foreach ($rawData->get()->toArray() as $item) {
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

        return new QuestionnaireGetListQueryResponse($result);
    }

    public function getList(Filters $filters): array
    {
        $builder = $this->model;
        foreach ($filters as $filter) {
            if (null !== $filter->value()->value()) {
                $builder = $builder->where(
                    $filter->field()->value(),
                    $filter->operator()->value(),
                    $filter->value()->value()
                );
            }
        }

        $rawData = $builder
            ->get()
            ->toArray();

        $result = [];

        foreach ($rawData as $datum) {
            $result[] = QuestionnaireTicketDto::fromState(
                $datum,
                new Uuid($datum['order_id']),
                new Uuid($datum['ticket_id']),
            );
        }

        return $result;
    }

    public function existByEmail(string $email): bool
    {
        return $this->model::whereEmail($email)->exists();
    }
}
