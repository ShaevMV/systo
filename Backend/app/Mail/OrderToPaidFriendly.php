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

/**
 * Mailable для отправки email при оплате Friendly-заказа.
 *
 * Отличается от OrderToPaid тем что:
 * - НЕ содержит ссылку на /myOrders (у гостей friendly-заказов нет ЛК)
 * - Использует отдельные blade-шаблоны (orderToPaidFriendly, TypeTicketMailOrderToPaidChildFriendly и т.д.)
 *
 * Принцип Open/Closed (SOLID):
 * - OrderToPaid ЗАКРЫТ для модификации
 * - OrderToPaidFriendly ОТКРЫТ как расширение для friendly-заказов
 */
class OrderToPaidFriendly extends Mailable
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

        // Определяем какой friendly-шаблон использовать
        $emailView = $this->tickets[0]->getEmailView();
        $isChildTicket = $emailView === 'TypeTicketMailOrderToPaidChild';

        $friendlyView = $isChildTicket ? 'TypeTicketMailOrderToPaidChildFriendly' : 'TypeTicketMailOrderToPaidFriendly1';

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

        $this->subject('Ваш билет на Систо 2026 оформлен');
        \Log::info('Friendly шаблон: ' . $friendlyView);
        $mail = $this->view('email.' . $friendlyView, [
            'festivalName' => $festivalName,
            'comment' => $this->comment,
            'promocode' => $this->promocode,
            'questionnaireLinks' => $isChildTicket ? $questionnaireLinks : [],
        ]);

        foreach ($this->tickets as $ticket) {
            if ($ticket->getFestivalView() === null) {
                continue;
            }
            $contents = $qrCodeService->createPdf($ticket);
            $mail->attachData($contents->output(), 'Оргвзнос ' . $ticket->getName() . '.pdf');
            \Log::info('Friendly: отправлен билет на имя '. $ticket->getName());
        }

        return $mail;
    }
}
