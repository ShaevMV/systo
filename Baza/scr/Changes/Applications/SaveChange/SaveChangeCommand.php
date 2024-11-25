<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\SaveChange;

use Baza\Shared\Domain\Bus\Command\Command;
use Carbon\Carbon;

class SaveChangeCommand implements Command
{
    private array $userIdList;

    public function __construct(
        array $userIdList,
        private Carbon $start,
        private ?int $id = null
    )
    {
        $this->userIdList = array_map(function ($item) {
            return (int)$item;
        }, $userIdList);
    }

    public function getStart(): Carbon
    {
        return $this->start;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdList(): array
    {
        return $this->userIdList;
    }
}
