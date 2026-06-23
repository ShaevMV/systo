<?php

declare(strict_types=1);

namespace Baza\Changes\Applications\SaveChange;

use Baza\Shared\Domain\Bus\Command\CommandBus;
use Baza\Shared\Infrastructure\Bus\Command\InMemorySymfonyCommandBus;
use Carbon\Carbon;
use Throwable;

class SaveChange
{
    private CommandBus $bus;

    public function __construct(
        SaveChangeCommandHandler $saveChangeCommandHandler
    )
    {
        $this->bus = new InMemorySymfonyCommandBus([
            SaveChangeCommand::class => $saveChangeCommandHandler
        ]);
    }

    /**
     * Открыть/обновить смену. $festivalId — фестиваль смены (TD-48); если не передан,
     * берётся config('baza.default_festival_id') (обратная совместимость: сидеры/легаси
     * вызовы без фестиваля продолжают работать на дефолтном фестивале).
     *
     * @throws Throwable
     */
    public function save(array $userIdList, Carbon $start, ?int $id = null, ?int $chiefId = null, ?string $festivalId = null): void
    {
        $festivalId = ($festivalId !== null && $festivalId !== '')
            ? $festivalId
            : (string) config('baza.default_festival_id');

        $this->bus->dispatch(new SaveChangeCommand($userIdList, $start, $festivalId, $id, $chiefId));
    }
}
