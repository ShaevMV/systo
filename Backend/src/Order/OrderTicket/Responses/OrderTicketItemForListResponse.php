<?php

declare(strict_types = 1);

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Shared\Domain\Bus\Query\Response;
use Tickets\Shared\Domain\Entity\AbstractionEntity;
use Tickets\Shared\Domain\ValueObject\Status;
use Tickets\Shared\Domain\ValueObject\Uuid;
use function Ramsey\Uuid\v1;

class OrderTicketItemForListResponse extends AbstractionEntity implements Response
{
    protected int $count;
    protected string $humanStatus;

    public function __construct(
        protected Uuid $id,
        protected int $kilter,
        protected string $email,
        protected string $name,
        protected float $price,
        protected array $guests,
        protected string $typeOfPaymentName,
        protected Status $status,
        protected string $dateBuy,
        protected array $listCorrectNextStatus,
        protected string $idBuy,
        protected ?string $lastComment = null,
        protected ?string $promoCode = null,
    ) {
        $this->count = count($guests);
        $this->humanStatus = $this->status->getHumanStatus();
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
        $status = new Status($data['status']);
        return new self(
            new Uuid($data['id']),
            $data['kilter'],
            $data['email'],
            $data['name'],
            (float) $data['price'] - (float) $data['discount'],
            $guests,
            $data['payment_name'],
            $status,
            $data['date'],
            $status->getListNextStatus(),
            $data['id_buy'],
            $data['last_comment'] ?? null,
            $data['promo_code'] ?? null,
        );
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @return GuestsDto[]
     */
    public function getGuests(): array
    {
        return $this->guests;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }
}
