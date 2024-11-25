<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Shared\Services\DefineService;
use Baza\Tickets\Applications\Scan\TicketResponseInterface;

class SearchResponse implements TicketResponseInterface
{
    /**
     * @param SpisokTicketResponse[] $spisok
     * @param ElTicketResponse[] $electron
     * @param FriendlyTicketResponse[] $drug
     * @param LiveTicketResponse[] $live
     */
    public function __construct(
        private array $spisok,
        private array $electron,
        private array $drug,
        private array $live,
        private array $auto,
    )
    {
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->spisok as $item) {
            $result[DefineService::SPISOK_TICKET][] = $item->toArray();
        }

        foreach ($this->electron as $item) {
            $result[DefineService::ELECTRON_TICKET][] = $item->toArray();
        }

        foreach ($this->drug as $item) {
            $result[DefineService::DRUG_TICKET][] = $item->toArray();
        }

        foreach ($this->live as $item) {
            $result[DefineService::LIVE_TICKET][] = $item->toArray();
        }

        foreach ($this->auto as $item) {
            $result[DefineService::AUTO_TICKET][] = $item->toArray();
        }

        return $result;
    }

    public static function fromState(array $data): TicketResponseInterface
    {
        return new self(
            $data[DefineService::SPISOK_TICKET],
            $data[DefineService::ELECTRON_TICKET],
            $data[DefineService::DRUG_TICKET],
            $data[DefineService::LIVE_TICKET],
            $data[DefineService::AUTO_TICKET]
        );
    }
}
