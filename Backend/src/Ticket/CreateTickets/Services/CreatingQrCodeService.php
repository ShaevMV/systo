<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Services;

use Endroid\QrCode\Label\Font\OpenSans;
use Pdf;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Tickets\Shared\Domain\ValueObject\Uuid;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\Dto\DataInfoForPdf;

class CreatingQrCodeService
{
    public function createQrCode(Uuid $ticketId): ResultInterface
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data(env('APP_BAZA_URL').'/newTickets/'.$ticketId->value())
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->logoPath(__DIR__ . '/assets/logo.png')
            ->labelText('') //TODO: Автомотизировать
            ->labelFont(new OpenSans(16))
            ->labelAlignment(new LabelAlignmentCenter())
            ->validateResult(false)
            ->build();
    }

    public function createPdf(TicketResponse $dataInfoForPdf): \Barryvdh\DomPDF\PDF
    {
        $qrCode = $this->createQrCode($dataInfoForPdf->getId());

        return Pdf::loadView('pdf', [
            'url' => $qrCode->getDataUri(),
            'name' => $dataInfoForPdf->getName(),
            'email' => $dataInfoForPdf->getEmail(),
            'kilter' => $dataInfoForPdf->getKilter()
        ]);
    }
}
