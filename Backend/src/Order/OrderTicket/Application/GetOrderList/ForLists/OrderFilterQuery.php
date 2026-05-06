<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForLists;

use InvalidArgumentException;
use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

/**
 * Запрос списка заказов-списков.
 *
 * Используется и для admin/manager (без curatorId), и для куратора (с curatorId = Auth::id).
 */
class OrderFilterQuery implements Query
{
    public function __construct(
        private Uuid    $festivalId,
        private ?string $email = null,
        private ?string $name = null,
        private ?Uuid   $locationId = null,
        private ?Uuid   $curatorId = null,
        private ?string $status = null,
        private ?string $project = null,
    ) {
    }

    public static function fromState(array $data, ?Uuid $curatorId = null): self
    {
        if (! isset($data['festivalId'])) {
            throw new InvalidArgumentException('festivalId обязательное поле!');
        }

        return new self(
            new Uuid($data['festivalId']),
            $data['email'] ?? null,
            $data['name'] ?? null,
            empty($data['locationId']) ? null : new Uuid($data['locationId']),
            $curatorId,
            $data['status'] ?? null,
            $data['project'] ?? null,
        );
    }

    public function getFestivalId(): Uuid
    {
        return $this->festivalId;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getLocationId(): ?Uuid
    {
        return $this->locationId;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curatorId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getProject(): ?string
    {
        return $this->project;
    }
}
