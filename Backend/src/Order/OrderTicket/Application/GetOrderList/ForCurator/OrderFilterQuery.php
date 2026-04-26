<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Application\GetOrderList\ForCurator;

use InvalidArgumentException;
use Shared\Domain\Bus\Query\Query;
use Shared\Domain\ValueObject\Uuid;

class OrderFilterQuery implements Query
{
    public function __construct(
        private Uuid    $festivalId,
        private ?string $email = null,
        private ?Uuid   $userId = null,
        private ?Uuid   $curatorId = null,
        private ?string $status = null,
    ) {
    }

    public static function fromState(array $data, ?Uuid $userId = null): self
    {
        if (! isset($data['festivalId'])) {
            throw new InvalidArgumentException('festivalId обязательное поле!');
        }

        return new self(
            new Uuid($data['festivalId']),
            $data['email'] ?? null,
            $userId,
            isset($data['curatorId']) ? new Uuid($data['curatorId']) : null,
            $data['status'] ?? null,
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

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getCuratorId(): ?Uuid
    {
        return $this->curatorId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
