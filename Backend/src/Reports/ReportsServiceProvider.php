<?php

namespace Tickets\Reports;

use Illuminate\Support\ServiceProvider;
use Tickets\Reports\Domain\Handlers\FriendlySummaryHandler;
use Tickets\Reports\Domain\ReportHandlerRegistry;
use Tickets\Reports\Infrastructure\GoogleSheetsClient;

class ReportsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportHandlerRegistry::class, function ($app) {
            $registry = new ReportHandlerRegistry();
            $registry->register(new FriendlySummaryHandler());
            return $registry;
        });

        $this->app->singleton(GoogleSheetsClient::class, function ($app) {
            return new GoogleSheetsClient(
                config('services.google.sheets_credentials')
            );
        });
    }
}
