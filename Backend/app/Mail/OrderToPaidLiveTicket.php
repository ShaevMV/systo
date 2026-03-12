<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Order\OrderTicket\Service\FestivalService;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;

class OrderToPaidLiveTicket extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private Uuid $ticketTypeId,
        private Uuid $typesOfPaymentId,
        private int  $kilter,
    )
    {

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(
        FestivalService                   $festivalService,
        TypesOfPaymentRepositoryInterface $typesOfPaymentRepository,
    ): static
    {
        ini_set('memory_limit', '-1');
        $festivalName = $festivalService->getFestivalNameByTicketType($this->ticketTypeId);
        $typesOfPaymentDto = $typesOfPaymentRepository->getItem($this->typesOfPaymentId);
        $this->subject('Ваш оргвзнос на Систо 2026 подтверждён');

        $mail = $this->view('email.' . empty(trim($typesOfPaymentDto->getEmail() ?? '')) ? 'orderToPaidLiveTicket' : $typesOfPaymentDto->getEmail(), [
            'festivalName' => $festivalName,
            'kilter' => $this->kilter,
        ]);

        return $mail;
    }
}
