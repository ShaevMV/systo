<?php

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use function Ramsey\Uuid\v1;

class OrderTicketItemForList extends AbstractionEntity implements Response
{
    protected int $count;

    public function __construct(
        protected Uuid $id,
        protected string $email,
        protected string $name,
        protected float $price,
        protected array $guests,
        protected string $typeOfPaymentName,
        protected string $humanStatus,
        protected Carbon $dateBuy,
        protected ?string $lastComment = null,
        protected ?string $promoCode = null,
    ) {
        $this->count = count($guests);
    }

    /**
     * @throws JsonException
     */
    public static function fromState(array $data): self
    {
        $guestsRaw = !is_array($data['guests']) ? Json::decode($data['guests'], 1) : $data['guests'];
        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = GuestsDto::fromState($guest);
        }

        return new self(
            new Uuid($data['id']),
            $data['email'],
            $data['name'],
            (float) $data['price'],
            $guests,
            $data['payment_name'],
            (new Status($data['status']))->getHumanStatus(),
            new Carbon($data['date']),
            $data['last_comment'] ?? null,
            $data['promo_code'] ?? null,
        );
    }
}
