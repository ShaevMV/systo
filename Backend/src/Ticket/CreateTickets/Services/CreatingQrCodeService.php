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

class CreatingQrCodeService
{
    private function createQrCode(Uuid $ticketId): ResultInterface
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
            ->logoPath(__DIR__ . '/assets/logo.png')
            ->labelText('Welcome to Solar Systo ' . date('Y')) //TODO: Автомотизировать
            ->labelFont(new OpenSans(16))
            ->labelAlignment(new LabelAlignmentCenter())
            ->validateResult(false)
            ->build();
    }

    public function createPdf(Uuid $ticketId, string $name, int $kilter): \Barryvdh\DomPDF\PDF
    {
        $qrCode = $this->createQrCode($ticketId);

        return Pdf::loadView('pdf', [
            'url' => $qrCode->getDataUri(),
            'name' => $name,
            'kilter' => $kilter
        ]);
    }
}
