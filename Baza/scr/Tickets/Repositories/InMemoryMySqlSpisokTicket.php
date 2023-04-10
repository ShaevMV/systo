<?php
declare(strict_types=1);

namespace Baza\Tickets\Repositories;

use App\Models\SpisokTicketModel;
use Baza\Tickets\Applications\Search\SpisokTicket\SpisokTicketResponse;

class InMemoryMySqlSpisokTicket implements SpisokTicketsRepositoryInterface
{

    public function __construct(
        private SpisokTicketModel $spisokTicketModel
    )
    {
    }


    public function search(int $kilter): ?SpisokTicketResponse
    {
        $data = $this->spisokTicketModel::whereKilter($kilter)->first()?->toArray();

        if(is_null($data)) {
            return null;
        }

        return SpisokTicketResponse::fromState($data);
    }
}
