<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\Report;

use Baza\Shared\Domain\Bus\Query\Response;
use Nette\Utils\JsonException;

class ReportForChangesResponse implements Response
{
    /**
     * @param ReportForChangesDto[] $reportList
     */
    public function __construct(
        private array $reportList
    )
    {
    }

    /**
     * @return array
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

}
