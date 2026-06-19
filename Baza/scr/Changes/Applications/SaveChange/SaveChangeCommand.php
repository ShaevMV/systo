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
        private string $festivalId,
        private ?int $id = null,
        private ?int $chiefId = null,
    )
    {
        $this->userIdList = array_map(function ($item) {
            return (int)$item;
        }, $userIdList);
    }

    /**
     * Начальник смены (user_id) — обязателен, если состав непуст (инвариант Ф2).
     */
    public function getChiefId(): ?int
    {
        return $this->chiefId;
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

    public function getFestivalId(): string
    {
        return $this->festivalId;
    }
}
