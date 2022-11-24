<?php

declare(strict_types=1);

namespace Tickets\Ordering\OrderTicket\Domain;

use DateTime;
use Exception;
use Nette\Utils\Json;
use Tickets\Ordering\OrderTicket\Domain\ProcessUserNotificationNewOrderTicket;
use Tickets\Shared\Domain\Aggregate\AggregateRoot;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;

final class OrderTicket extends AggregateRoot
{
    public function __construct(
        protected Uuid $id,
        protected array $guests,
        protected DateTime $date,
        protected string $idBuy,
        protected Status $status,
        protected ?string $promoCod = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public static function create(array $data, string $email): self
    {
        $result = new self(
            new Uuid($data['id']),
            Json::decode($data['guests']),
            new DateTime($data['date']),
            $data['types_of_payment_id'],
            new Status($data['status'] ?? Status::NEW),
            $data['promo_code'] ?? null
        );

        $result->record(new ProcessUserNotificationNewOrderTicket($email));

        return $result;
    }
}
