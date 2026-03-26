<?php

namespace Tickets\Reports\Domain;

interface ReportHandlerInterface
{
    public function getType(): string;

    public function getName(): string;

    public function getHeaders(): array;

    public function getData(array $filters): array;

    public function formatRow(object $row, int $index): array;
}
