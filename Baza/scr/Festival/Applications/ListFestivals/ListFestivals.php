<?php

declare(strict_types=1);

namespace Baza\Festival\Applications\ListFestivals;

use Baza\Shared\Domain\Bus\Query\QueryBus;
use Baza\Shared\Infrastructure\Bus\Query\InMemorySymfonyQueryBus;

/**
 * Чтение реестра фестивалей (TD-48, PR-1). Тонкий слой — БД в репозитории.
 * Стиль по образцу Baza\ShiftSchedule\Applications\ListSchedules\ListSchedules.
 */
class ListFestivals
{
    private QueryBus $bus;

    public function __construct(
        ListFestivalsQueryHandler $listFestivalsQueryHandler,
    ) {
        $this->bus = new InMemorySymfonyQueryBus([
            ListFestivalsQuery::class => $listFestivalsQueryHandler,
        ]);
    }

    /**
     * Весь реестр фестивалей (для экрана управления).
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->ask(false);
    }

    /**
     * Фестивали, доступные для КПП — для выбора при открытии смены.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeForKpp(): array
    {
        return $this->ask(true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function ask(bool $onlyActiveForKpp): array
    {
        /** @var FestivalListResponse $result */
        $result = $this->bus->ask(new ListFestivalsQuery($onlyActiveForKpp));

        return $result->getFestivals();
    }
}
