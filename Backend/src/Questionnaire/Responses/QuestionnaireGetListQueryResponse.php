<?php

declare(strict_types=1);

namespace Tickets\Questionnaire\Responses;

use Illuminate\Support\Collection;
use Shared\Domain\Bus\Query\Response;
use Tickets\Questionnaire\Dto\QuestionnaireTicketDto;

class QuestionnaireGetListQueryResponse implements Response
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
