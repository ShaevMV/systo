<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\ValueObject\Status;
use Baza\Shared\Services\DefineService;
use Baza\Shared\Services\ShowSearchWordService;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;
use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class FriendlyTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $kilter,
        protected string  $name,
        protected string  $project,
        protected string  $email,
        protected string  $seller,
        protected Carbon  $date_order,
        protected Status  $status,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::DRUG_TICKET,
            'kilter' => $this->kilter,
            'name' => $this->name,
            'email' => $this->email,
            'project' => $this->project,
            'comment' => $this->comment,
            'status_human' => $this->status->getHumanStatus(),
            'status' => (string)$this->status,
            'date_order' => $this->date_order->format('d M Y'),
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
            'seller' => $this->seller,
            'color' => Color::COLOR_FRIENDLY,
        ];
    }

    public static function fromState(array $data, ?string $q = null): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['kilter'],
            ShowSearchWordService::insertTag($data['name'], $q),
            ShowSearchWordService::insertTag($data['project'], $q),
            ShowSearchWordService::insertTag($data['email'], $q),
            ShowSearchWordService::insertTag($data['seller'], $q),
            Carbon::parse($data['date_order']),
            new Status($data['status']),
            ShowSearchWordService::insertTag($data['comment'], $q),
            $data['change_id'] ?? null,
            $date_change
        );
    }
}
