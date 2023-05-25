<?php

namespace Tickets\Order\OrderTicket\Repositories;

use App\Models\Festival\FestivalModel;
use Tickets\Order\OrderTicket\Dto\Festival\FestivalDto;
use Tickets\Shared\Domain\ValueObject\Uuid;

class InMemoryMySqlFestivalRepository implements FestivalRepositoryInterface
{

    public function __construct(
        private FestivalModel $model
    )
    {
    }

    public function get(Uuid $id): FestivalDto
    {
        $result = $this->model->find($id->value());

        if(is_null($result)) {
            throw new \DomainException("Фестиваль {$id->value()} не найден");
        }

        return FestivalDto::fromState($result->toArray());
    }
}
