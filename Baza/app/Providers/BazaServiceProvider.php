<?php

namespace App\Providers;

use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\InMemoryMySqlElTicket;
use Baza\Tickets\Repositories\InMemoryMySqlSpisokTicket;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class BazaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ElTicketsRepositoryInterface::class, InMemoryMySqlElTicket::class);
        $this->app->bind(SpisokTicketsRepositoryInterface::class, InMemoryMySqlSpisokTicket::class);
    }
}
