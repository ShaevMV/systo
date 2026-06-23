<?php

declare(strict_types=1);

namespace Baza\Festival\Dto;

/**
 * Фестиваль из реестра Vhod (TD-48, PR-1) — пассивный DTO (по образцу ShiftScheduleDto).
 *
 * `active` — зеркало org-каталога (информативно). `activeForKpp` — локальный флаг КПП:
 * попадает ли фестиваль в выбор при открытии смены и в офлайн-снимок.
 */
final class FestivalDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?int $year,
        public readonly bool $active,
        public readonly bool $activeForKpp,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     */
    public static function fromState(array $row): self
    {
        return new self(
            id: (string) $row['id'],
            name: (string) ($row['name'] ?? ''),
            year: isset($row['year']) && $row['year'] !== null ? (int) $row['year'] : null,
            active: (bool) ($row['active'] ?? true),
            activeForKpp: (bool) ($row['active_for_kpp'] ?? true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'year' => $this->year,
            'active' => $this->active,
            'active_for_kpp' => $this->activeForKpp,
        ];
    }
}
