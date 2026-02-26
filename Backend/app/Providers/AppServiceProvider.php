<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Sentry\SentrySdk;
use Sentry\ClientBuilder;
use Sentry\State\Hub;
use Sentry\Monolog\Handler;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

// Регистрируем канал sentry_logs
        Log::extend('sentry_logs', function ($app, $config) {
            // Создаем клиент Sentry
            $client = ClientBuilder::create([
                'dsn' => env('SENTRY_LARAVEL_DSN'),
                'environment' => env('APP_ENV', 'production'),
            ])->getClient();

            // Создаем Hub с клиентом
            $hub = new Hub($client);

            // Устанавливаем Hub в качестве текущего
            SentrySdk::setCurrentHub($hub);

            // Создаем Monolog handler с Hub
            $handler = new Handler($hub);

            // Возвращаем Monolog логгер
            return new \Monolog\Logger('sentry', [$handler]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
