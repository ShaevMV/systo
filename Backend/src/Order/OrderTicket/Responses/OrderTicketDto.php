<?php

declare(strict_types=1);

namespace Tickets\Order\OrderTicket\Responses;

use Carbon\Carbon;
use Database\Seeders\FestivalSeeder;
use Exception;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tickets\Order\OrderTicket\Dto\OrderTicket\GuestsDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\TicketTypeDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\TypesOfPaymentDto;
use Tickets\Order\OrderTicket\ValueObject\CommentForOrder;
use Shared\Domain\Bus\Query\Response;
use Shared\Domain\Entity\AbstractionEntity;
use Shared\Domain\ValueObject\Status;
use Shared\Domain\ValueObject\Uuid;

final class OrderTicketDto extends AbstractionEntity implements Response
{
    protected Carbon $created_at;
    protected Carbon $updated_at;
    protected Uuid $festival_id;

    public function __construct(
        protected Uuid $user_id,
        private string $email,
        protected array $guests,
        protected Carbon $date,
        private PriceDto $price,
        protected Status $status,
        protected string $id_buy,
        protected Uuid $types_of_payment_id,
        protected Uuid $ticket_type_id,
        protected ?string $promo_code = null,
        protected ?Uuid $id = null,
        private ?string $last_comment = null,
        ?Carbon $created_at = null,
        ?Carbon $updated_at = null,
        private ?TicketTypeDto $ticket_type = null,
        private ?TypesOfPaymentDto $type_of_payment = null,
        private array $commentForOrder = [],
    ) {
        $this->festival_id = new Uuid(FestivalSeeder::ID_FOR_2023_FESTIVAL);
        $this->created_at = $created_at ?? new Carbon();
        $this->updated_at = $updated_at ?? new Carbon();
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public static function fromState(array $data): self
    {
        $id = !is_null($data['id'] ?? null) ? new Uuid($data['id']) : null;
        $ticketType = !is_null($data['ticket_type'] ?? null) ? TicketTypeDto::fromState($data['ticket_type']) : null;
        $typeOfPayment = !is_null($data['type_of_payment'] ?? null) ? TypesOfPaymentDto::fromState($data['type_of_payment']) : null;

        $createAt = !is_null($data['created_at'] ?? null) ? new Carbon($data['created_at']) : null;
        $updatedAt = !is_null($data['updated_at'] ?? null) ? new Carbon($data['updated_at']) : null;

        $guestsRaw = !is_array($data['guests']) ? Json::decode($data['guests'], 1) : $data['guests'];
        $guests = [];
        foreach ($guestsRaw as $guest) {
            $guests[] = GuestsDto::fromState($guest);
        }

        $comments = [];
        foreach ($data['comments'] ?? [] as $comment) {
            $comments[] = CommentForOrder::fromState($comment);
        }

        return new self(
            new Uuid($data['user_id']),
            $data['email'],
            $guests,
            new Carbon($data['date']),
            PriceDto::fromState($data),
            new Status($data['status']),
            $data['id_buy'],
            new Uuid($data['types_of_payment_id']),
            new Uuid($data['ticket_type_id']),
            $data['promo_code'] ?? null,
            $id,
            $data['last_comment'] ?? null,
            $createAt,
            $updatedAt,
            $ticketType,
            $typeOfPayment,
            $comments
        );
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDateBuy(): Carbon
    {
        return $this->date;
    }

    public function getDateCreate(): Carbon
    {
        return $this->created_at;
    }

    public function getTypesOfPaymentId(): Uuid
    {
        return $this->types_of_payment_id;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getPromoCode(): ?string
    {
        return $this->promo_code;
    }

    public function getUserId(): Uuid
    {
        return $this->user_id;
    }

    /**
     * @return GuestsDto[]
     */
    public function getGuests(): array
    {
        return $this->guests;
    }

    /**
     * @return Uuid
     */
    public function getTicketTypeId(): Uuid
    {
        return $this->ticket_type_id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPrice(): PriceDto
    {
        return $this->price;
    }

    /**
     * @throws JsonException
     */
    public function toArray(): array
    {
        $result = parent::toArray();
        $result['guests'] = Json::encode($result['guests']);
        $result['price'] = $this->price->getPrice();
        $result['discount'] = $this->price->getDiscount();

        return $result;
    }

    public function updateUpdatedAt(): void
    {
        $this->updated_at = new Carbon();
    }

    public function getName(): string
    {
        if (is_null($this->ticket_type)) {
            throw new \InvalidArgumentException('Нет данных о стоимости заказа');
        }

        return $this->ticket_type->getName();
    }

    public function getLastComment(): ?string
    {
        return $this->last_comment;
    }

    public function getTypesOfPaymentName(): string
    {
        if (is_null($this->type_of_payment)) {
            throw new \InvalidArgumentException('Нет данных о типе оплаты');
        }

        return $this->type_of_payment->getName();
    }

    public function getCommentForOrder(): array
    {
        return $this->commentForOrder;
    }

    public function setId(?Uuid $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFestivalId(): Uuid
    {
        return $this->festival_id;
    }
}
