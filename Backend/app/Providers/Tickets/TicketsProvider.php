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
use Tickets\Auto\Repositories\AutoRepositoryInterface;
use Tickets\Auto\Repositories\InMemoryMySqlAutoRepository;
use Tickets\Location\Repositories\InMemoryMySqlLocationRepository;
use Tickets\Location\Repositories\LocationRepositoryInterface;
use Tickets\Option\Repositories\InMemoryMySqlOptionRepository;
use Tickets\Option\Repositories\OptionRepositoryInterface;
use Tickets\OptionPrice\Repositories\InMemoryMySqlOptionPriceRepository;
use Tickets\OptionPrice\Repositories\OptionPriceRepositoryInterface;
use Tickets\Template\Repositories\InMemoryMySqlTemplateRepository;
use Tickets\Template\Repositories\TemplateRepositoryInterface;
use Tickets\Template\Service\TemplateRenderer;
use Tickets\TemplateBinding\Domain\TemplateBindingResolver;
use Tickets\TemplateBinding\Repositories\InMemoryMySqlTemplateBindingRepository;
use Tickets\TemplateBinding\Repositories\TemplateBindingRepositoryInterface;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\EmailDelivery\Repositories\InMemoryMySqlEmailMessageRepository;
use Tickets\BazaDelivery\Repositories\BazaDeliveryRepositoryInterface;
use Tickets\BazaDelivery\Repositories\InMemoryMySqlBazaDeliveryRepository;
use Tickets\TicketTypePrice\Repositories\InMemoryMySqlTicketTypePriceRepository;
use Tickets\TicketTypePrice\Repositories\TicketTypePriceRepositoryInterface;
use Tickets\User\Account\Repositories\InMemoryMySqlUserRepositories;
use Tickets\User\Account\Repositories\UserRepositoriesInterface;
use Tickets\QrOrder\Application\Issuance\IssuanceStrategyRegistry;
use Tickets\QrOrder\Application\Issuance\Strategy\FriendlyIssuanceStrategy;
use Tickets\QrOrder\Application\Issuance\Strategy\ListIssuanceStrategy;
use Tickets\QrOrder\Application\Issuance\Strategy\LiveIssuanceStrategy;
use Tickets\QrOrder\Application\Issuance\Strategy\RegularIssuanceStrategy;
use Tickets\QrOrder\Repositories\InMemoryMySqlQrIssuanceRepository;
use Tickets\QrOrder\Repositories\InMemoryMySqlQrOrderRepository;
use Tickets\QrOrder\Repositories\QrIssuanceRepositoryInterface;
use Tickets\QrOrder\Repositories\QrOrderRepositoryInterface;

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
        $this->app->bind(TemplateRepositoryInterface::class, InMemoryMySqlTemplateRepository::class);
        // singleton: Mustache\Engine кеширует токенайзинг; на заказ из N гостей createPdf не плодит N движков.
        $this->app->singleton(TemplateRenderer::class);
        // Привязки шаблонов (Часть B): репозиторий + чистый резолвер (singleton — без состояния).
        $this->app->bind(TemplateBindingRepositoryInterface::class, InMemoryMySqlTemplateBindingRepository::class);
        $this->app->singleton(TemplateBindingResolver::class);
        // Трекинг доставки писем (Ф2): репозиторий статусов писем.
        $this->app->bind(EmailMessageRepositoryInterface::class, InMemoryMySqlEmailMessageRepository::class);
        // Трекинг доставки билетов в Baza (AF-4): репозиторий статусов доставки.
        $this->app->bind(BazaDeliveryRepositoryInterface::class, InMemoryMySqlBazaDeliveryRepository::class);
        $this->app->bind(TicketTypePriceRepositoryInterface::class, InMemoryMySqlTicketTypePriceRepository::class);
        $this->app->bind(OptionRepositoryInterface::class, InMemoryMySqlOptionRepository::class);
        $this->app->bind(OptionPriceRepositoryInterface::class, InMemoryMySqlOptionPriceRepository::class);
        $this->app->bind(AutoRepositoryInterface::class, InMemoryMySqlAutoRepository::class);
        // Приём заказов от витрины qr (API №1) + выдача билетов (API №2b)
        $this->app->bind(QrOrderRepositoryInterface::class, InMemoryMySqlQrOrderRepository::class);
        $this->app->bind(QrIssuanceRepositoryInterface::class, InMemoryMySqlQrIssuanceRepository::class);

        // Реестр стратегий выдачи билетов по qr-заказу (type_order → стратегия).
        // Новая стратегия (friendly/список/live) добавляется одной строкой здесь.
        $this->app->singleton(IssuanceStrategyRegistry::class, static function ($app): IssuanceStrategyRegistry {
            return new IssuanceStrategyRegistry([
                $app->make(RegularIssuanceStrategy::class),
                $app->make(FriendlyIssuanceStrategy::class),
                $app->make(ListIssuanceStrategy::class),
                $app->make(LiveIssuanceStrategy::class),
            ]);
        });
    }
}
