<?php

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Entity\AbstractionEntity;
use Carbon\Carbon;

class ReportForChangesDto extends AbstractionEntity
{
    public function __construct(
        protected int     $id,
        protected string  $userName,
        protected int     $count_live_tickets,
        protected int     $count_el_tickets,
        protected int     $count_drug_tickets,
        protected int     $count_spisok_tickets,
        protected Carbon  $start,
        protected ?Carbon $end = null,
    )
    {
    }

    public static function fromState(array $data): self
    {
        $end = !is_null($data['end']) ? Carbon::parse($data['end']) : null;

        return new self(
            $data['id'],
            $data['user_name'],
            $data['count_live_tickets'],
            $data['count_el_tickets'],
            $data['count_drug_tickets'],
            $data['count_spisok_tickets'],
            Carbon::parse($data['start']),
            $end
        );
    }
}
