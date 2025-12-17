<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;
use Tickets\Order\OrderTicket\Dto\OrderTicket\QuestionnaireTicketDto;

class QuestionnaireGetItemQueryResponse implements Response
{
    /**
     * @var Collection
     */
    private Collection $collection;

    public function __construct(
        array $questionnaire
    )
    {
        $this->collection = new Collection($questionnaire);
    }

    public function toArray():array
    {
        $result=[];
        /** @var QuestionnaireTicketDto $item */
        foreach ($this->collection as $item)
        {
            $result[]=$item->toArray();
        }
        return $result;
    }
}
