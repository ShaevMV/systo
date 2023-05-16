<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Entity\AbstractionEntity;

class ReportTotalDto extends AbstractionEntity
{
    protected int $total = 0;

    public function __construct(
        protected int $live = 0,
        protected int $drug = 0,
        protected int $spisok = 0,
        protected int $el = 0,
    )
    {
        $this->total = $this->live + $this->drug + $this->spisok + $this->el;
    }

    /**
     * @param ReportForChangesDto[] $data
     * @return self
     */
    public static function fromList(array $data):self
    {
        $live = $drug = $spisok = $el = 0;
        foreach ($data as $datum) {
            $live += $datum->getCountLiveTickets();
            $drug += $datum->getCountDrugTickets();
            $spisok += $datum->getCountSpisokTickets();
            $el += $datum->getCountElTickets();
        }

        return new self(
            $live,
            $drug,
            $spisok,
            $el,
        );
    }
}
