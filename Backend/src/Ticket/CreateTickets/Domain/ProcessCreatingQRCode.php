<?php

namespace Tickets\Ticket\CreateTickets\Domain;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tickets\Shared\Domain\Bus\EventJobs\DomainEvent;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class ProcessCreatingQRCode implements ShouldQueue, DomainEvent
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Uuid $ticketId,
        private string $name,
    ) {
    }

    /**
     * @throws \Throwable
     */
    public function handle(
        CreatingQrCodeService $codeInPdfService,
    ): void
    {
        try {
            $qrCode = $codeInPdfService->createQrCode($this->ticketId);
            $codeInPdfService->createPdf(
                $qrCode,
                $this->ticketId,
                $this->name,
            );
        } catch (\Throwable $throwable) {
            \Log::error($throwable->getMessage());
            throw $throwable;
        }
    }
}
