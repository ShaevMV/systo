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
        protected int     $count_auto_tickets,
        protected int     $count_parking_tickets,
        protected int     $count_parking_free_tickets,
        protected int     $count_parking_cross_country_tickets,
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
            $data['count_auto_tickets'],
            $data['count_parking_tickets'],
            $data['count_parking_free_tickets'],
            $data['count_parking_cross-country_tickets'],
            Carbon::parse($data['start']),
            $end
        );
    }

    public function getCountLiveTickets(): int
    {
        return $this->count_live_tickets;
    }

    public function getCountElTickets(): int
    {
        return $this->count_el_tickets;
    }

    public function getCountDrugTickets(): int
    {
        return $this->count_drug_tickets;
    }

    public function getCountSpisokTickets(): int
    {
        return $this->count_spisok_tickets;
    }

    public function getCountAutoTickets(): int
    {
        return $this->count_auto_tickets;
    }

    public function getCountParkingTickets(): int
    {
        return $this->count_parking_tickets;
    }

    public function getCountParkingFreeTickets(): int
    {
        return $this->count_parking_free_tickets;
    }

    public function getCountParkingCrossCountryTickets(): int
    {
        return $this->count_parking_cross_country_tickets;
    }
}
