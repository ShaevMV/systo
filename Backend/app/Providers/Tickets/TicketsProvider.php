<?php

declare(strict_types = 1);

namespace App\Providers\Tickets;

use Illuminate\Support\ServiceProvider;
use Tickets\Questionnaire\Repositories\QuestionnaireRepositoryInterface;
use Tickets\QuestionnaireType\Repositories\InMemoryMySqlQuestionnaireTypeRepository;
use Tickets\QuestionnaireType\Repositories\QuestionnaireTypeRepositoryInterface;
use Tickets\Questionnaire\Service\QuestionnaireValidationService;
use Tickets\Festival\Repositories\InMemoryMySqlTicketTypeRepository;
use Tickets\Festival\Repositories\InMemoryMySqlTypesOfPayment;
use Tickets\Festival\Repositories\TicketTypeInterfaceRepository;
use Tickets\Festival\Repositories\TypesOfPaymentInterface;
use Tickets\Order\OrderTicket\Repositories\CommentRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\FestivalRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\InMemoryInviteLinkRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlCommentRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlFestivalRepository;
use Tickets\Order\OrderTicket\Repositories\InMemoryMySqlOrderTicketRepository;
use Tickets\Order\OrderTicket\Repositories\InviteLinkRepositoryInterface;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;
use Tickets\PromoCode\Repositories\ExternalPromoCodeInterface;
use Tickets\PromoCode\Repositories\InMemoryMySqlExternalPromoCode;
use Tickets\PromoCode\Repositories\InMemoryMySqlPromoCode;
use Tickets\PromoCode\Repositories\PromoCodeInterface;
use Tickets\Questionnaire\Repositories\InMemoryMySqlQuestionnaireRepository;
use Tickets\Ticket\CreateTickets\Repositories\InMemoryMySqlTicketsRepository;
use Tickets\Ticket\CreateTickets\Repositories\TicketsRepositoryInterface;
use Tickets\TicketType\Repository\InMemoryTicketTypeRepository;
use Tickets\TicketType\Repository\TicketTypeRepositoryInterface;
use Tickets\TypesOfPayment\Repositories\InMemoryMySqlTypesOfPaymentRepository;
use Tickets\TypesOfPayment\Repositories\TypesOfPaymentRepositoryInterface;
use Tickets\History\Repositories\HistoryRepositoryInterface;
use Tickets\History\Repositories\InMemoryMySqlHistoryRepository;
use Tickets\Location\Repositories\InMemoryMySqlLocationRepository;
use Tickets\Location\Repositories\LocationRepositoryInterface;
use Tickets\User\Account\Repositories\InMemoryMySqlUserRepositories;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;

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
        $this->app->bind(UserRepositoriesInterface::class, InMemoryMySqlUserRepositories::class);
        $this->app->bind(OrderTicketRepositoryInterface::class, InMemoryMySqlOrderTicketRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, InMemoryMySqlCommentRepository::class);
        $this->app->bind(TicketsRepositoryInterface::class, InMemoryMySqlTicketsRepository::class);
        $this->app->bind(FestivalRepositoryInterface::class, InMemoryMySqlFestivalRepository::class);
        $this->app->bind(ExternalPromoCodeInterface::class, InMemoryMySqlExternalPromoCode::class);
        $this->app->bind(QuestionnaireRepositoryInterface::class, InMemoryMySqlQuestionnaireRepository::class);
        $this->app->bind(InviteLinkRepositoryInterface::class, InMemoryInviteLinkRepository::class);
        $this->app->bind(TypesOfPaymentRepositoryInterface::class, InMemoryMySqlTypesOfPaymentRepository::class);
        $this->app->bind(TicketTypeRepositoryInterface::class, InMemoryTicketTypeRepository::class);
        $this->app->bind(QuestionnaireTypeRepositoryInterface::class, InMemoryMySqlQuestionnaireTypeRepository::class);
        $this->app->singleton(QuestionnaireValidationService::class, QuestionnaireValidationService::class);
        $this->app->bind(HistoryRepositoryInterface::class, InMemoryMySqlHistoryRepository::class);
        $this->app->bind(LocationRepositoryInterface::class, InMemoryMySqlLocationRepository::class);
    }
}
