<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Shared\Domain\Entity\EntityDataInterface;
use Shared\Domain\ValueObject\Uuid;

final class GuestsDto implements EntityDataInterface
{
    public function __construct(
        protected string $value,
        protected ?string $email,
        protected Uuid   $id,
        protected ?Uuid   $festivalId,
    )
    {
    }

    public static function fromState(array $data, ?string $festivalId = null): self
    {
        $id = isset($data['id']) && !empty($data['id']) ? new Uuid($data['id']) : Uuid::random();
        $festivalUuid = !empty($data['festival_id'] ?? $festivalId) ? new Uuid($data['festival_id'] ?? $festivalId) : null;
        return new self(
            $data['value'],
            $data['email'] ?? null,
            $id,
            $festivalUuid
        );
    }

    public function updateId(): void
    {
        $this->id = Uuid::random();
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return Json::encode([
            'value' => $this->value,
            'id' => $this->id->value(),
            'email' => $this->email ?? null,
            'festival_id' => $this->festivalId->value(),
        ]);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFestivalId(): ?Uuid
    {
        return $this->festivalId;
    }

    public function getEmail(): ?string
    {
        return $this->email ?? null;
    }
}
