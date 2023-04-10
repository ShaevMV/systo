<?php

namespace Baza\Tickets\Repositories;

use App\Models\ElTicketsModel;
use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Tickets\Applications\Search\ElTicket\ElTicketResponse;

class InMemoryMySqlElTicket implements ElTicketsRepositoryInterface
{

    public function __construct(
        private ElTicketsModel $elTicketsModel
    )
    {
    }

    public function search(Uuid $id): ?ElTicketResponse
    {
        $data = $this->elTicketsModel::whereUuid($id->value())->first()?->toArray();

        if(is_null($data)) {
            return null;
        }


        return ElTicketResponse::fromState($data);
    }
}
