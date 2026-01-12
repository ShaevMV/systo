<?php
declare(strict_types = 1);

namespace App\Providers\Tickets;

use Illuminate\Support\ServiceProvider;
use Tickets\Order\InfoForOrder\Repositories\InMemoryMySqlTicketTypeRepository;
use Tickets\Order\InfoForOrder\Repositories\InMemoryMySqlTypesOfPayment;
use Tickets\Order\InfoForOrder\Repositories\TicketTypeInterfaceRepository;
use Tickets\Order\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\InMemoryInviteLinkRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlCommentRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlFestivalRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlQuestionnaireRepository;
use Tickets\Order\OrderTicket\Repositories\InviteLinkRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\QuestionnaireRepositoryInterface;
use Tickets\PromoCode\Repositories\ExternalPromoCodeInterface;
use Tickets\PromoCode\Repositories\InMemoryMySqlExternalPromoCode;
use Tickets\PromoCode\Repositories\InMemoryMySqlPromoCode;
use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\Ticket\CreateTickets\Repositories\InMemoryMySqlTicketsRepository;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\User\Account\Repositories\AccountInterface;
use Tickets\User\Account\Repositories\InMemoryMySqlAccount;

class TicketsProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(TicketTypeInterfaceRepository::class, InMemoryMySqlTicketTypeRepository::class);
        $this->app->bind(TypesOfPaymentInterface::class, InMemoryMySqlTypesOfPayment::class);
        $this->app->bind(PromoCodeInterface::class, InMemoryMySqlPromoCode::class);
        $this->app->bind(AccountInterface::class, InMemoryMySqlAccount::class);
        $this->app->bind(OrderTicketRepositoryInterface::class, InMemoryMySqlOrderTicketRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, InMemoryMySqlCommentRepository::class);
        $this->app->bind(TicketsRepositoryInterface::class, InMemoryMySqlTicketsRepository::class);
        $this->app->bind(FestivalRepositoryInterface::class, InMemoryMySqlFestivalRepository::class);
        $this->app->bind(ExternalPromoCodeInterface::class, InMemoryMySqlExternalPromoCode::class);
        $this->app->bind(QuestionnaireRepositoryInterface::class, InMemoryMySqlQuestionnaireRepository::class);
        $this->app->bind(InviteLinkRepositoryInterface::class, InMemoryInviteLinkRepository::class);
    }
}
