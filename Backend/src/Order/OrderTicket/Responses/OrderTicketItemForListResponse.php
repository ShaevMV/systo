<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;
use function Ramsey\Uuid\v1;

class OrderTicketItemForListResponse extends AbstractionEntity implements Response
{
    protected int $count;
    protected string $humanStatus;

    /**
     * @param Uuid $id
     * @param int $kilter
     * @param string $email
     * @param string $name
     * @param int $price
     * @param GuestsDto[] $guests
     * @param string $typeOfPaymentName
     * @param Status $status
     * @param string $dateBuy
     * @param array $listCorrectNextStatus
     * @param string $idBuy
     * @param float $priceWithoutDiscount
     * @param string|null $lastComment
     * @param string|null $promoCode
     * @param int $discount
     * @param string|null $city
     */
    public function __construct(
        protected Uuid    $id,
        protected int     $kilter,
        protected string  $email,
        protected string  $name,
        protected int     $price,
        protected array   $guests,
        protected string  $typeOfPaymentName,
        protected Status  $status,
        protected string  $dateBuy,
        protected array   $listCorrectNextStatus,
        protected string  $idBuy,
        protected float   $priceWithoutDiscount,
        protected ?string $lastComment = null,
        protected ?string $promoCode = null,
        protected int     $discount = 0,
        protected ?string $city = null,
    )
    {
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
            $guests[] = GuestsDto::fromState($guest, $data['festival_id']);
        }
        $status = new Status($data['status']);
        return new self(
            new Uuid($data['id']),
            $data['kilter'],
            $data['email'],
            $data['name'],
            (int)$data['price'] - (int)$data['discount'],
            $guests,
            $data['payment_name'],
            $status,
            $data['date'],
            $status->getListNextStatus(),
            $data['id_buy'],
            (int)$data['price'],
            $data['last_comment'] ?? null,
            $data['promo_code'] ?? null,
            (int)$data['discount'],
            $data['city']
        );
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getPrice(): int
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

    public function getGuestsByFestivalId(Uuid $festivalId): array
    {
        $result = [];
        foreach ($this->guests as $guest) {
            if ($guest->getFestivalId()->equals($festivalId)) {
                $result[] = $guest;
            }
        }

        return $result;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKilter(): int
    {
        return $this->kilter;
    }

    public function getPriceWithoutDiscount(): float
    {
        return $this->priceWithoutDiscount;
    }

    public function getDiscount(): int
    {
        return $this->discount;
    }

    public function setGuests(array $guests): self
    {
        $this->guests = $guests;
        $this->count = count($guests);
        return $this;
    }
}
