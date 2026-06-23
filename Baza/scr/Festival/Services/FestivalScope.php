<?php

declare(strict_types=1);

namespace Baza\Festival\Services;

/**
 * Request-scoped «активный фестиваль» для выборок билетов (TD-48, PR-3).
 *
 * Раньше фестиваль был зашит константой в каждом ticket-репозитории. Теперь репозитории
 * (el/spisok/friendly/auto) читают фильтр ОТСЮДА, а контроллер скана/впуска выставляет его
 * по флагу `baza.festival_isolation` + фестивалю ОТКРЫТОЙ СМЕНЫ сотрудника.
 *
 * Дефолт (никто не трогал) = фильтр по config('baza.default_festival_id') — это в точности
 * прежнее поведение (изоляция OFF, один фестиваль). Поэтому связывается как singleton:
 * один общий объект на запрос, репозитории держат ссылку и видят мутации контроллера.
 *
 *  - useFestival($id) — фильтровать по конкретному фестивалю (фестиваль смены при ON);
 *  - useAny()         — без фильтра (глобально): нужно скану, чтобы НАЙТИ билет чужого
 *                       фестиваля и показать «жёлтый» вердикт, а не «не найден»;
 *  - reset()          — вернуть дефолт (фильтр по дефолтному фестивалю).
 */
class FestivalScope
{
    private bool $any = false;
    private bool $none = false;
    private ?string $festivalId = null;

    public function useFestival(string $festivalId): void
    {
        $this->festivalId = $festivalId;
        $this->any = false;
        $this->none = false;
    }

    public function useAny(): void
    {
        $this->any = true;
        $this->none = false;
    }

    /**
     * Fail-closed (TD-48): выборки не возвращают НИЧЕГО. Для случая «изоляция ON, но фестиваль
     * смены не определён» (нет открытой смены / festival_id смены пуст) — чтобы не отдать молча
     * дефолтный фестиваль (его ПДн) вместо пустого. Лучше пусто, чем чужой/непредназначенный.
     */
    public function useNone(): void
    {
        $this->none = true;
        $this->any = false;
    }

    public function reset(): void
    {
        $this->any = false;
        $this->none = false;
        $this->festivalId = null;
    }

    /** Применяется ли фильтр по фестивалю (false = глобально). */
    public function appliesFilter(): bool
    {
        return ! $this->any;
    }

    /** Текущий фестиваль фильтра (дефолт — config('baza.default_festival_id')). */
    public function festivalId(): string
    {
        return $this->festivalId ?? (string) config('baza.default_festival_id');
    }

    /**
     * Навесить фильтр фестиваля на Eloquent-builder (no-op в режиме useAny).
     *
     * @template T
     * @param  T  $query
     * @return T
     */
    public function apply($query, string $column = 'festival_id')
    {
        if ($this->none) {
            return $query->whereRaw('1 = 0'); // fail-closed: ничего не вернуть
        }
        if (! $this->appliesFilter()) {
            return $query; // useAny — глобально
        }
        $festival = $this->festivalId();
        if ($festival === '') {
            return $query; // защита инварианта 1: пустой default_festival_id → НЕ "WHERE = ''" (обнулило бы выдачу), а глобально
        }

        return $query->where($column, '=', $festival);
    }

    /**
     * Мягкий фильтр для live/parking (TD-48, PR-6): где festival_id у строки может быть NULL
     * (пул номеров без жёсткой привязки к фестивалю). Матчит фестиваль ИЛИ NULL — чтобы
     * непомеченные номера не терялись из впуска, а помеченные изолировались.
     *
     * @template T
     * @param  T  $query
     * @return T
     */
    public function applyLenient($query, string $column = 'festival_id')
    {
        if ($this->none) {
            return $query->whereRaw('1 = 0'); // fail-closed
        }
        if (! $this->appliesFilter()) {
            return $query; // useAny — глобально
        }

        $festival = $this->festivalId();
        if ($festival === '') {
            return $query; // пустой default → глобально (см. apply)
        }

        return $query->where(function ($q) use ($festival, $column): void {
            $q->where($column, '=', $festival)->orWhereNull($column);
        });
    }
}
