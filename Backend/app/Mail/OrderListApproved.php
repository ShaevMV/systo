<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Festival\FestivalModel;
use App\Models\Location\LocationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Shared\Domain\ValueObject\Uuid;
use Tickets\Ticket\CreateTickets\Application\GetTicket\TicketResponse;
use Tickets\Ticket\CreateTickets\Services\CreatingQrCodeService;

/**
 * Письмо при одобрении заказа-списка (статус APPROVE_LIST).
 *
 * Отличия от OrderToPaid/OrderToPaidFriendly:
 * - Не привязан к ticket_type — использует Location.
 * - Шаблон письма берётся из Location.email_template (по-умолчанию orderListApproved).
 * - Название фестиваля — напрямую из таблицы festivals по festival_id заказа.
 */
class OrderListApproved extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param TicketResponse[] $tickets
     */
    public function __construct(
        private array $tickets,
        private Uuid  $festivalId,
        private ?Uuid $locationId,
    ) {
    }

    public function build(CreatingQrCodeService $qrCodeService): static
    {
        ini_set('memory_limit', '-1');

        $festivalName = FestivalModel::query()
            ->whereId($this->festivalId->value())
            ->value('name') ?? '';

        $location = $this->locationId !== null
            ? LocationModel::query()->whereId($this->locationId->value())->first()
            : null;

        $emailView = ! empty($location?->email_template)
            ? $location->email_template
            : 'orderListApproved';

        $locationName = $location?->name ?? '';

        $this->subject('Список на ' . $festivalName . ' одобрен');

        $mail = $this->view('email.' . $emailView, [
            'festivalName' => $festivalName,
            'locationName' => $locationName,
        ]);

        // У заказа-списка нет ticket_type → нет festivalView; createPdf фолбэчит на дефолтный шаблон 'pdf'
        foreach ($this->tickets as $ticket) {
            $contents = $qrCodeService->createPdf($ticket);
            $mail->attachData($contents->output(), 'Билет ' . $ticket->getName() . '.pdf');
        }

        return $mail;
    }
}
