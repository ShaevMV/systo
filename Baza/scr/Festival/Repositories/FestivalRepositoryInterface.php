<?php

declare(strict_types=1);

namespace Baza\Festival\Repositories;

/**
 * Реестр фестивалей на Vhod (TD-48, PR-1). БД — ТОЛЬКО в реализации (Dependency Rule).
 *
 * Проекции возвращаются массивами (паттерн ShiftSchedule): `id`, `name`, `year`,
 * `active`, `active_for_kpp`.
 */
interface FestivalRepositoryInterface
{
    /**
     * Весь реестр фестивалей (для экрана управления).
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array;

    /**
     * Фестивали, доступные для КПП (`active_for_kpp = true`) — для выбора при открытии смены.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listActiveForKpp(): array;

    /**
     * Фестиваль по id или null.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array;

    /**
     * Существует ли фестиваль с таким id (для 404 вместо 500).
     */
    public function exists(string $id): bool;

    /**
     * Название фестиваля по id (для подписи «вы на фестивале X», карточек) или null.
     */
    public function nameFor(string $id): ?string;

    /**
     * Названия пачкой [id => name] — против N+1 в списках.
     *
     * @param  array<int, string>  $ids
     * @return array<string, string>
     */
    public function namesByIds(array $ids): array;

    /**
     * Включить/выключить доступность фестиваля для КПП (`active_for_kpp`).
     */
    public function setActiveForKpp(string $id, bool $active): bool;
}
