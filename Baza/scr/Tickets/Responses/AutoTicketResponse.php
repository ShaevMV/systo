<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Domain\ValueObject\Status;
use Baza\Shared\Domain\ValueObject\Uuid;
use Baza\Shared\Services\DefineService;
use Baza\Shared\Services\ShowSearchWordService;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;
use Baza\Tickets\ValueObject\Color;
use Carbon\Carbon;

class AutoTicketResponse implements TicketResponseInterface
{
    public function __construct(
        protected int     $id,
        protected string  $auto,
        protected string  $project,
        protected string  $curator,
        protected ?string $comment = null,
        protected ?int    $change_id = null,
        protected ?Carbon $date_change = null
    )
    {
    }

    public function toArray(): array
    {
        return [
            'type' => DefineService::AUTO_TICKET,
            'id' => $this->id,
            'auto' => $this->auto,
            'project' => $this->project,
            'curator' => $this->curator,
            'change_id' => $this->change_id ?? null,
            'date_change' => $this->date_change?->format('d M Y H:i:s'),
            'comment' => $this->comment,
            'color' => Color::COLOR_AUTO,
        ];
    }

    public static function fromState(array $data, ?string $q = null): self
    {
        $date_change = !is_null($data['date_change'] ?? null) ? Carbon::parse($data['date_change']) : null;

        return new self(
            $data['id'],
            ShowSearchWordService::insertTag($data['auto'], $q),
            ShowSearchWordService::insertTag($data['project'], $q),
            ShowSearchWordService::insertTag($data['curator'], $q),
            ShowSearchWordService::insertTag($data['comment'], $q),
            $data['change_id'] ?? null,
            $date_change,
        );
    }
}
