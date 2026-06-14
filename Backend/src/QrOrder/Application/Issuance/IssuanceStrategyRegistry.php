<?php

declare(strict_types=1);

namespace Tickets\QrOrder\Application\Issuance;

use LogicException;
use Tickets\QrOrder\Domain\ValueObject\TypeOrder;

/**
 * Реестр стратегий выдачи: type_order → стратегия. Состав регистрируется в TicketsProvider.
 *
 * Неизвестный/пустой type_order → fallback на стратегию REGULAR (владелец — связующее звено,
 * гарантирует корректность данных контракта).
 */
final class IssuanceStrategyRegistry
{
    /** @var array<string, IssuanceStrategyInterface> */
    private array $map = [];

    /**
     * @param iterable<IssuanceStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->map[$strategy->typeOrder()] = $strategy;
        }
    }

    public function resolve(?string $typeOrder): IssuanceStrategyInterface
    {
        $key = TypeOrder::normalize($typeOrder);

        return $this->map[$key]
            ?? $this->map[TypeOrder::REGULAR]
            ?? throw new LogicException('Не зарегистрирована стратегия выдачи REGULAR');
    }
}
