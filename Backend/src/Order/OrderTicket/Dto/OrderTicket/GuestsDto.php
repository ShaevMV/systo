<?php

declare(strict_types =1);

namespace Tickets\Order\OrderTicket\Dto\OrderTicket;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Shared\Domain\Entity\EntityDataInterface;

final class GuestsDto implements EntityDataInterface
{
    public function __construct(
        protected string $value
    ){
    }

    public static function fromState(array $data):self
    {
        return new self($data['value']);
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
        $r = Json::encode(['value'=>$this->value]);
        return Json::encode(['value'=>$this->value]);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
