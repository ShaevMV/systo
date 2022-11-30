<?php
declare(strict_types = 1);

namespace App\Providers\Tickets;

use Illuminate\Support\ServiceProvider;
use Tickets\Ordering\InfoForOrder\Repositories\InMemoryMySqlPromoCode;
use Tickets\Ordering\InfoForOrder\Repositories\InMemoryMySqlTicketType;
use Tickets\Ordering\InfoForOrder\Repositories\InMemoryMySqlTypesOfPayment;
use Tickets\Ordering\InfoForOrder\Repositories\PromoCodeInterface;
use Tickets\Ordering\InfoForOrder\Repositories\TicketTypeInterface;
use Tickets\Ordering\InfoForOrder\Repositories\TypesOfPaymentInterface;
use Tickets\Ordering\OrderTicket\Repositories\InMemoryMySqlOrderTicket;
use Tickets\Ordering\OrderTicket\Repositories\OrderTicketInterface;
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
        $this->app->bind(TicketTypeInterface::class, InMemoryMySqlTicketType::class);
        $this->app->bind(TypesOfPaymentInterface::class, InMemoryMySqlTypesOfPayment::class);
        $this->app->bind(PromoCodeInterface::class, InMemoryMySqlPromoCode::class);
        $this->app->bind(AccountInterface::class, InMemoryMySqlAccount::class);
        $this->app->bind(OrderTicketInterface::class, InMemoryMySqlOrderTicket::class);
    }
}
