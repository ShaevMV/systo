<?php

declare(strict_types=1);

namespace App\Providers;

use Baza\Changes\Repositories\ChangesRepositoryInterface;
use Baza\Changes\Repositories\InMemoryMySqlChangesRepository;
use Baza\EntryOutbox\Repositories\EntryOutboxRepositoryInterface;
use Baza\EntryOutbox\Repositories\InMemoryMySqlEntryOutboxRepository;
use Baza\Ingest\Repositories\IngestRepositoryInterface;
use Baza\Ingest\Repositories\InMemoryMySqlIngestRepository;
use Baza\Permission\Repositories\InMemoryMySqlRolePermissionRepository;
use Baza\Permission\Repositories\RolePermissionRepositoryInterface;
use Baza\Sync\Repositories\InMemoryMySqlSyncRepository;
use Baza\Sync\Repositories\SyncRepositoryInterface;
use Baza\Tickets\Repositories\AutoTicketRepositoryInterface;
use Baza\Tickets\Repositories\ElTicketsRepositoryInterface;
use Baza\Tickets\Repositories\FriendlyTicketRepositoryInterface;
use Baza\Tickets\Repositories\InMemoryMySqlAutoTicket;
use Baza\Tickets\Repositories\InMemoryMySqlElTicket;
use Baza\Tickets\Repositories\InMemoryMySqlFriendlyTicket;
use Baza\Tickets\Repositories\InMemoryMySqlLiveTicket;
use Baza\Tickets\Repositories\InMemoryMySqlParkingTicketRepository;
use Baza\Tickets\Repositories\InMemoryMySqlSpisokTicket;
use Baza\Tickets\Repositories\InMemoryMySqlTicketSearch;
use Baza\Tickets\Repositories\InMemoryMySqlUserRepository;
use Baza\Tickets\Repositories\LiveTicketRepositoryInterface;
use Baza\Tickets\Repositories\ParkingTicketRepositoryInterface;
use Baza\Tickets\Repositories\SpisokTicketsRepositoryInterface;
use Baza\Tickets\Repositories\TicketSearchRepositoryInterface;
use Baza\Tickets\Repositories\UserRepositoryInterface;
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
        $this->app->bind(ParkingTicketRepositoryInterface::class, InMemoryMySqlParkingTicketRepository::class);
        $this->app->bind(UserRepositoryInterface::class, InMemoryMySqlUserRepository::class);
        $this->app->bind(SyncRepositoryInterface::class, InMemoryMySqlSyncRepository::class);
        $this->app->bind(RolePermissionRepositoryInterface::class, InMemoryMySqlRolePermissionRepository::class);
        $this->app->bind(IngestRepositoryInterface::class, InMemoryMySqlIngestRepository::class);
        $this->app->bind(EntryOutboxRepositoryInterface::class, InMemoryMySqlEntryOutboxRepository::class);
        $this->app->bind(TicketSearchRepositoryInterface::class, InMemoryMySqlTicketSearch::class);
    }
}
