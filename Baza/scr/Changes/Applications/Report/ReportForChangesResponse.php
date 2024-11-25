<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Bus\Query\Response;
use Nette\Utils\JsonException;

class ReportForChangesResponse implements Response
{
    private ReportTotalDto $reportTotalDto;


    /**
     * @param ReportForChangesDto[] $reportList
     */
    public function __construct(
        private array $reportList
    )
    {
        $this->reportTotalDto = ReportTotalDto::fromList($reportList);
    }

    /**
     * @throws JsonException
     */
    public function getReportList(): array
    {
        $result = [];

        foreach ($this->reportList as $item) {
            $result[] = $item->toArray();
        }

        return $result;
    }

    public function getReportTotalDto(): ReportTotalDto
    {
        return $this->reportTotalDto;
    }

}
