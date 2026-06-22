<?php

namespace App\Mail;

use App\Mail\Concerns\RendersDbTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Helpers\FestivalHelper;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

class OrderToPaid extends Mailable
{
    use Queueable, SerializesModels, RendersDbTemplate;

    /**
     * @param TicketResponse[] $tickets
     * @param int|null $orderNo номер заказа для подстановки {{ kilter }} (qr → external_order_no);
     *                          null → берётся kilter первого билета (классический org-флоу).
     */
    public function __construct(
        private array $tickets,
        private Uuid $ticketTypeId,
        private ?string $comment = null,
        private ?string $promocode = null,
        private ?int $orderNo = null,
    )
    {
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        CreatingQrCodeService $qrCodeService,
        FestivalService $festivalService,
    ): static
    {
        ini_set('memory_limit', '-1');
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);

        $emailView = $this->tickets[0]->getEmailView();
        $isChildTicket = $emailView === 'TypeTicketMailOrderToPaidChild';

        $questionnaireLinks = [];
        if ($isChildTicket) {
            foreach ($this->tickets as $ticket) {
                $orderId = $ticket->getOrderId();
                $ticketId = $ticket->getId();
                if ($orderId && $ticketId) {
                    $questionnaireLinks[] = [
                        'name' => $ticket->getName(),
                        'url' => "https://org.spaceofjoy.ru/questionnaire/{$orderId->value()}/{$ticketId->value()}"
                    ];
                }
            }
        }

        // Номер заказа для {{ kilter }}: явно переданный (qr → external_order_no) либо kilter
        // первого билета (классический org-флоу). Раньше kilter в контекст НЕ передавался —
        // в письме order_paid плейсхолдер {{ kilter }} оставался пустым (в order_created работал).
        $kilter = $this->orderNo ?? (($this->tickets[0] ?? null)?->getKilter());

        $this->subject('Ваш оргвзнос на ' . $festivalName . ' подтверждён');
        // Активный DB-шаблон (Mustache) или fallback на blade email.{slug} — см. RendersDbTemplate.
        $mail = $this->renderDbOrView(empty($emailView) ? 'orderToPaid' : $emailView, [
            'festivalName' => $festivalName,
            'kilter' => $kilter,
            'comment' => $this->comment,
            'promocode' => $this->promocode,
            'questionnaireLinks' => $questionnaireLinks,
        ]);

        foreach ($this->tickets as $ticket) {
            if ($ticket->getFestivalView() === null) {
                continue;
            }
            $contents = $qrCodeService->createPdf($ticket);
            $mail->attachData($contents->output(), 'Оргвзнос ' . $ticket->getName() . '.pdf');
            \Log::info('Отправлен билет на имя '. $ticket->getName());
        }

        return $mail;
    }
}
