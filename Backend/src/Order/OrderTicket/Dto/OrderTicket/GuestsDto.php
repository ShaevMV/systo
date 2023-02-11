<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Shared\Domain\Entity\EntityDataInterface;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class GuestsDto implements EntityDataInterface
{
    public function __construct(
        protected string $value,
        protected Uuid   $id
    )
    {
    }

    public static function fromState(array $data): self
    {
        $id = isset($data['id']) && !empty($data['id']) ? new Uuid($data['id']) : Uuid::random();

        return new self(
            $data['value'],
            $id,
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
}
