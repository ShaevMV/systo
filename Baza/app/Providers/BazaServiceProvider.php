<?php

declare(strict_types=1);

namespace App\Providers;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Changes\Repositories\InMemoryMySqlChangesRepository;
use Baza\Tickets\Repositories\AutoTicketRepositoryInterface;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Repositories\InMemoryMySqlAutoTicket;
use Baza\Tickets\Repositories\InMemoryMySqlElTicket;
use Baza\Tickets\Repositories\InMemoryMySqlFriendlyTicket;
use Baza\Tickets\Repositories\InMemoryMySqlLiveTicket;
use Baza\Tickets\Repositories\InMemoryMySqlSpisokTicket;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class BazaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ElTicketsRepositoryInterface::class, InMemoryMySqlElTicket::class);
        $this->app->bind(SpisokTicketsRepositoryInterface::class, InMemoryMySqlSpisokTicket::class);
        $this->app->bind(FriendlyTicketRepositoryInterface::class, InMemoryMySqlFriendlyTicket::class);
        $this->app->bind(LiveTicketRepositoryInterface::class, InMemoryMySqlLiveTicket::class);
        $this->app->bind(ChangesRepositoryInterface::class, InMemoryMySqlChangesRepository::class);
        $this->app->bind(AutoTicketRepositoryInterface::class, InMemoryMySqlAutoTicket::class);
    }
}
