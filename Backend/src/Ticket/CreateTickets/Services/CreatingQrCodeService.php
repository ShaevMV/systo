<?php

declare(strict_types=1);

namespace Tickets\Ticket\CreateTickets\Services;

use Endroid\QrCode\Label\Font\OpenSans;
use Pdf;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Label\Alignment\LabelAlignmentCenter;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Service\TemplateRenderer;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;

class CreatingQrCodeService
{
    public function createQrCode(string $ticketId): ResultInterface
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data('/newTickets/'.$ticketId)
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
        $qrCode = $this->createQrCode($dataInfoForPdf->getId()->value());

        $slug = $dataInfoForPdf->getFestivalView() ?? 'pdf';
        $vars = [
            'url' => $qrCode->getDataUri(),
            'name' => $dataInfoForPdf->getName(),
            'email' => $dataInfoForPdf->getEmail(),
            'kilter' => $dataInfoForPdf->getKilter(),
        ];

        // Активный шаблон в БД → рендер из БД (admin меняет билет без деплоя).
        // Нет записи → fallback на blade-файл (старое поведение, без изменений).
        $html = $this->resolveTemplateHtml($slug, $vars);

        return $html !== null
            ? Pdf::loadHTML($html)
            : Pdf::loadView($slug, $vars);
    }

    /**
     * Отрендерить активный DB-шаблон PDF по slug. null → нет активной записи → вызывающий
     * падает на blade-файл. Резолвер и рендерер берём из контейнера (сервис создаётся в очереди).
     *
     * @param array<string, mixed> $vars
     */
    public function resolveTemplateHtml(string $slug, array $vars): ?string
    {
        $template = app(TemplateRepositoryInterface::class)->findActive($slug, TemplateKind::PDF);

        if ($template === null) {
            return null;
        }

        return app(TemplateRenderer::class)->render($template->getRenderBody(), $vars);
    }
}
