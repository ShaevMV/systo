<?php

namespace App\Mail;

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
    use Queueable, SerializesModels;

    /**
     * @param TicketResponse[] $tickets
     */
    public function __construct(
        private array $tickets,
        private Uuid $ticketTypeId,
        private ?string $comment = null,
        private ?string $promocode = null,
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

        $this->subject('Ваш оргвзнос на Систо 2026 подтверждён');
        \Log::info('Шаблон  '. $emailView);
        $mail = $this->view('email.'. (empty($emailView) ? 'orderToPaid' : $emailView),[
            'festivalName' => $festivalName,
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
