<?php

declare(strict_types=1);

namespace Tickets\QuestionnaireType\Repositories;

use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Tickets\QuestionnaireType\Dto\QuestionnaireTypeDto;

interface QuestionnaireTypeRepositoryInterface
{
    public function getList(Filters $filters, Order $orderBy): Collection;

    public function getItem(Uuid $id): QuestionnaireTypeDto;

    public function create(QuestionnaireTypeDto $data): bool;

    public function editItem(Uuid $id, QuestionnaireTypeDto $data): bool;

    public function remove(Uuid $id): bool;
}
