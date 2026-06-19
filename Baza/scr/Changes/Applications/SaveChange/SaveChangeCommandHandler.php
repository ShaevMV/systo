<?php

namespace Baza\Changes\Applications\SaveChange;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Shared\Domain\Bus\Command\CommandHandler;

class SaveChangeCommandHandler implements CommandHandler
{
    public function __construct(
        private ChangesRepositoryInterface $changesRepository
    )
    {
    }

    public function __invoke(SaveChangeCommand $command)
    {
        $userIdList = $command->getUserIdList();
        $chiefId = $command->getChiefId();

        // Если главный указан — он обязан входить в состав смены (Ф2). Требование
        // «главный обязателен» при создании человеком — на уровне контроллера/формы
        // (ChangesController::save), чтобы программные вызовы (сидеры) оставались гибкими.
        if ($chiefId !== null && ! in_array($chiefId, $userIdList, true)) {
            throw new \DomainException('Начальник смены должен входить в состав смены.');
        }

        if (! $this->changesRepository->updateOrCreate(
            $userIdList,
            $command->getStart(),
            $command->getFestivalId(),
            $command->getId(),
            $chiefId,
        )) {
            throw new \DomainException('Не получилось сохранить смену');
        }
    }
}
