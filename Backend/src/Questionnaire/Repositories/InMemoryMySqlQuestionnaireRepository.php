<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Repositories;

use App\Models\Questionnaire\QuestionnaireModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Questionnaire\Domain\ValueObject\QuestionnaireStatus;
use Shared\Questionnaire\Dto\QuestionnaireTicketDto;
use Shared\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Throwable;
use Illuminate\Support\Collection;

class InMemoryMySqlQuestionnaireRepository implements QuestionnaireRepositoryInterface
{
    public function __construct(
        private QuestionnaireModel $model
    )
    {
    }

    function getSql($query)
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . addslashes($binding) . "'";
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
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }


    public function getList(Filters $filters): Collection
    {
        Log::info($this->getSql(FilterBuilder::build($this->model, $filters)
            ->orderBy('created_at','DESC')));

        return FilterBuilder::build($this->model, $filters)
            ->orderBy('created_at','DESC')
            ->get()
            ->each(fn(QuestionnaireModel $model) =>QuestionnaireTicketDto::fromState($model->toArray()));
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
        if (!$rawData) {
            throw new \DomainException("Анкета с $id не найдена");
        }
        $rawData->status = $questionnaireStatus;
        return $rawData->save();
    }

    public function findByEmail(string $email): ?QuestionnaireTicketDto
    {
        if (!$data = $this->model::whereEmail($email)->first()?->toArray()) {
            return null;
        }

        return QuestionnaireTicketDto::fromState($data);
    }
}
