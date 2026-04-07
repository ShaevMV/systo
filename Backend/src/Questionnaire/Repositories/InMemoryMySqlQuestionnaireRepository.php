<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use App\Models\Questionnaire\QuestionnaireModel;
use App\Models\Questionnaire\QuestionnaireTypeModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class InMemoryMySqlQuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function __construct(
        private QuestionnaireModel $model
    ) {
    }

    public function getSql($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'".addslashes($binding)."'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }

    /**
     * @throws Throwable
     */
    public function create(QuestionnaireTicketDto $questionnaireTicketDto): bool
    {
        $data = $questionnaireTicketDto->toArrayForMySql();
        DB::beginTransaction();
        try {
            $rawModel = $this->model;
            $isChildQuestionnaire = $this->isChildType($questionnaireTicketDto->getQuestionnaireTypeId());

            // Приоритет 1: поиск по order_id + ticket_id (всегда)
            if ($questionnaireTicketDto->getTicketId() && $questionnaireTicketDto->getOrderId()) {
                $rawModel = $this->model::whereOrderId($questionnaireTicketDto->getOrderId()->value())
                    ->whereTicketId($questionnaireTicketDto->getTicketId()->value());
            }
            // Приоритет 2: поиск по email (НО НЕ для детских анкет!)
            elseif ($questionnaireTicketDto->getEmail() && ! $isChildQuestionnaire) {
                $rawModel = $this->model::whereEmail($questionnaireTicketDto->getEmail());
            }

            if ($rawModel->exists()) {
                $rawModel->update($data);
            } else {
                $this->model->insert(
                    array_merge($data,
                        [
                            'created_at' => (string) (new Carbon()),
                            'updated_at' => (string) (new Carbon()),
                        ]
                    ));
            }
            DB::commit();

            return true;
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    /**
     * Проверяет, является ли тип анкеты "child"
     */
    private function isChildType(?string $questionnaireTypeId): bool
    {
        if (! $questionnaireTypeId) {
            return false;
        }

        try {
            $type = QuestionnaireTypeModel::find($questionnaireTypeId);

            return $type && $type->code === 'child';
        } catch (\Throwable $e) {
            Log::warning('Failed to check questionnaire type: '.$e->getMessage());

            return false;
        }
    }

    public function getList(Filters $filters): Collection
    {
        return FilterBuilder::build($this->model, $filters)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->each(fn (QuestionnaireModel $model) => QuestionnaireTicketDto::fromState($model->toArray()));
    }

    public function existByEmail(string $email): bool
    {
        return $this->model::whereEmail($email)->exists();
    }

    public function get(int $id): QuestionnaireTicketDto
    {
        if (! $rawData = $this->model::whereId($id)->first()?->toArray()) {
            throw new \DomainException("Анкета с $id не найдена");
        }

        return QuestionnaireTicketDto::fromState($rawData);
    }

    public function cacheStatus(int $id, string $questionnaireStatus): bool
    {
        $rawData = $this->model::find($id);
        if (! $rawData) {
            throw new \DomainException("Анкета с $id не найдена");
        }
        $rawData->status = $questionnaireStatus;

        return $rawData->save();
    }

    public function findByEmail(?string $email): ?QuestionnaireTicketDto
    {
        if (! $email || ! $data = $this->model::whereEmail($email)->first()?->toArray()) {
            return null;
        }

        return QuestionnaireTicketDto::fromState($data);
    }

    public function findByOrderIdAndTicketId(Uuid $orderId, Uuid $ticketId): ?QuestionnaireTicketDto
    {
        $data = $this->model::whereOrderId($orderId->value())
            ->whereTicketId($ticketId->value())
            ->first()?->toArray();

        return $data ? QuestionnaireTicketDto::fromState($data) : null;
    }
}
