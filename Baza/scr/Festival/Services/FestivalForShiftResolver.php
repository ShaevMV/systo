<?php

declare(strict_types=1);

namespace Baza\Festival\Services;

use Baza\Festival\Repositories\FestivalRepositoryInterface;
use DomainException;

/**
 * Резолвер «какой фестиваль у открываемой смены» (TD-48, PR-2).
 *
 * Единый источник правил выбора фестиваля смены — чтобы не дублировать их между
 * PWA (ShiftController) и legacy Blade (ChangesController). БД не трогает (через репо).
 *
 * Правила:
 *  - явный festivalId передан → должен быть в реестре и доступен для КПП (active_for_kpp), иначе ошибка;
 *  - не передан, ровно один активный фестиваль → авто-выбор его (один фест — норма дня);
 *  - не передан, реестр пуст → graceful-fallback на config('baza.default_festival_id')
 *    (обратная совместимость: смены работают и без наполненного реестра);
 *  - не передан, активных несколько → ошибка «выберите фестиваль» (нельзя открыть смену «не там»).
 */
class FestivalForShiftResolver
{
    public function __construct(
        private FestivalRepositoryInterface $festivals,
    ) {
    }

    /**
     * @throws DomainException если фестиваль обязателен (несколько активных) или недоступен
     */
    public function resolve(?string $festivalId): string
    {
        $active = $this->festivals->listActiveForKpp();

        if ($festivalId !== null && $festivalId !== '') {
            foreach ($active as $f) {
                if ((string) $f['id'] === $festivalId) {
                    return $festivalId;
                }
            }

            throw new DomainException('Выбранный фестиваль недоступен для КПП.');
        }

        $count = count($active);

        if ($count === 1) {
            return (string) $active[0]['id'];
        }

        if ($count === 0) {
            // Реестр ещё не наполнен (нет синка из org) — не ломаем открытие смены.
            return (string) config('baza.default_festival_id');
        }

        throw new DomainException('Выберите фестиваль смены.');
    }
}
