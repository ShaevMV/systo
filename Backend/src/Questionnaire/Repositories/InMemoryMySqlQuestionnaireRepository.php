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
use Tickets\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
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
            $rawModel = $this->model;
            if ($questionnaireTicketDto->getTicketId() && $questionnaireTicketDto->getOrderId()) {
                $rawModel = $this->model::whereOrderId($questionnaireTicketDto->getOrderId()->value())
                    ->whereTicketId($questionnaireTicketDto->getTicketId()->value());
            }
            if ($questionnaireTicketDto->getEmail()) {
                $rawModel = $this->model::whereEmail($questionnaireTicketDto->getEmail());
            }
            if ($rawModel->exists()) {
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
            );
        }

        return $result;
    }

    public function existByEmail(string $email): bool
    {
        return $this->model::whereEmail($email)->exists();
    }

    public function get(int $id): QuestionnaireTicketDto
    {
        if (!$rawData = $this->model::whereId($id)->first()?->toArray()) {
            throw new \DomainException("Анкета с $id не найдена");
        }

        return QuestionnaireTicketDto::fromState($rawData);
    }

    public function cacheStatus(int $id, QuestionnaireStatus $questionnaireStatus): bool
    {
        $rawData = $this->model::find($id);
        if(!$rawData) {
            throw new \DomainException("Анкета с $id не найдена");
        }
        $rawData->status = $questionnaireStatus;
        return $rawData->save();
    }
}
