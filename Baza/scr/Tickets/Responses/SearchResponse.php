<?php

declare(strict_types=1);

namespace Baza\Tickets\Responses;

use Baza\Tickets\Applications\Scan\TicketResponseInterface;
use Baza\Tickets\Services\DefineService;

class SearchResponse implements TicketResponseInterface
{
    /**
     * @param SpisokTicketResponse[] $spisok
     * @param ElTicketResponse[] $electron
     * @param FriendlyTicketResponse[] $drug
     */
    public function __construct(
        private array $spisok,
        private array $electron,
        private array $drug,
    )
    {
    }

    public function toArray(): array
    {
        foreach ($this->spisok as $item) {
            $result[DefineService::SPISOK_TICKET][] = $item->toArray();
        }

        foreach ($this->electron as $item) {
            $result[DefineService::ELECTRON_TICKET][] = $item->toArray();
        }

        foreach ($this->drug as $item) {
            $result[DefineService::DRUG_TICKET][] = $item->toArray();
        }

        return $result;
    }

    public static function fromState(array $data): TicketResponseInterface
    {
        return new self(
            $data[DefineService::SPISOK_TICKET],
            $data[DefineService::ELECTRON_TICKET],
            $data[DefineService::DRUG_TICKET],
        );
    }
}
