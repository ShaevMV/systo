<?php

declare(strict_types=1);

namespace Tickets\Ordering\Domain;

use DateTime;
use Exception;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class OrderTicket extends AggregateRoot
{
    public function __construct(
        protected Uuid $id,
        protected array $guests,
        protected string $email,
        protected DateTime $date,
        protected string $idBuy,
        protected Status $status,
        protected ?string $promoCod = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public static function fromState(array $data): self
    {
        new self(
            new Uuid($data['id']),
            $data['guests'],
            $data['email'],
            new DateTime($data['date']),
            $data['idBuy'],
            $data['promoCod'] ?? null
        );
    }
}
