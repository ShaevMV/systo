<?php

declare(strict_types=1);

namespace Shared\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

class CreatingQrCodeService
{
    public function createQrCode(string $ticketId, string $prefix): ResultInterface
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data('http://baza.spaceofjoy.ru/search?q=' . $prefix . $ticketId)
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

    public function createPdf(string $id, string $name, string $email, string $prefix, ?string $project = null): \Barryvdh\DomPDF\PDF
    {
        $qrCode = $this->createQrCode($id, $prefix);

        return Pdf::loadView('pdf', [
            'url' => $qrCode->getDataUri(),
            'name' => $name,
            'email' => $email,
            'kilter' => $id,
            'project' => $project
        ]);
    }
}
