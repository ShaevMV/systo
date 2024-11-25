<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Responses;

use Shared\Domain\Bus\Query\Response;

class UrlsTicketPdfResponse implements Response
{
    public function __construct(
        private array $urls
    ) {
    }

    public function getUrls(): array
    {
        return $this->urls;
    }
}
