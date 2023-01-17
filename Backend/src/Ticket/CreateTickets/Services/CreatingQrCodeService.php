<?php

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

class CreatingQrCodeService
{
    public function createQrCode(Uuid $ticketId): ResultInterface
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($ticketId->value())
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->logoPath(__DIR__.'/assets/logo.png')
            ->labelText('Welcome to Solar Systo 2023') //TODO: Автомотизировать
            ->labelFont(new OpenSans(16))
            ->labelAlignment(new LabelAlignmentCenter())
            ->validateResult(false)
            ->build();
    }

    public function createPdf(ResultInterface $qrCode, Uuid $ticketId, string $name): void
    {
        $pdf = Pdf::loadView('pdf', [
            'url' => $qrCode->getDataUri(),
            'name' => $name,
        ]);

        $pdf->save(storage_path("app/public/tickets/{$ticketId->value()}.pdf"));
    }
}
